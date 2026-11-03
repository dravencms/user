<?php declare(strict_types=1);

namespace Dravencms\User\Console;

use Doctrine\ORM\EntityManagerInterface;
use Dravencms\Model\User\Entities\{Group, User};
use Dravencms\Security\PasswordManager;
use Dravencms\User\Acl\{AclGrantStore, AclRegistry};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption, StreamableInputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class AclBootstrapCommand extends Command
{
    public function __construct(private EntityManagerInterface $manager, private AclGrantStore $grants, private AclRegistry $registry, private PasswordManager $passwords)
    {
        parent::__construct('user:acl:bootstrap');
    }

    protected function configure(): void
    {
        $this->setDescription('Create or recover an admin account with explicit group-management permission')
            ->addArgument('email', InputArgument::REQUIRED, 'Admin account email')
            ->addOption('group', null, InputOption::VALUE_REQUIRED, 'Group to assign', 'Administrator')
            ->addOption('password-stdin', null, InputOption::VALUE_NONE, 'Read the password for a new account from standard input')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Explicitly grant every currently registered permission once');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = strtolower(trim($input->getArgument('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new \InvalidArgumentException('Invalid administrator email.'); }
        $user = $this->manager->getRepository(User::class)->findOneBy(['email' => $email, 'namespace' => 'Admin']);
        if ($user === null) {
            if ($input->getOption('password-stdin')) {
                $stream = $input instanceof StreamableInputInterface ? $input->getStream() : null;
                $password = rtrim(stream_get_contents($stream ?? STDIN), "\r\n");
            } elseif ($input->isInteractive()) {
                $question = (new Question('New administrator password: '))->setHidden(true)->setHiddenFallback(false);
                $password = $this->getHelper('question')->ask($input, $output, $question);
            } else {
                throw new \InvalidArgumentException('A new account requires --password-stdin or interactive password entry.');
            }
            if (!is_string($password) || strlen($password) < 8) { throw new \InvalidArgumentException('Use a password of at least eight characters.'); }
            $user = new User('Admin', 'Administrator', $email, $password, 'Admin', $this->passwords->hash(...));
        }
        $group = $this->manager->getRepository(Group::class)->findOneBy(['name' => $input->getOption('group')]);
        if ($group === null) { $group = new Group($input->getOption('group'), 'Administrators', 'd9534f'); }
        $user->addGroup($group);
        $this->manager->persist($user);
        $keys = $input->getOption('all') ? array_keys($this->registry->getPermissions()) : ['user.edit'];
        $this->grants->assign($group, array_values(array_unique([...$this->grants->getExplicitKeys($group), ...$keys])));
        $output->writeln('Administrator access configured. Newly introduced permissions are not automatically granted.');
        return Command::SUCCESS;
    }
}
