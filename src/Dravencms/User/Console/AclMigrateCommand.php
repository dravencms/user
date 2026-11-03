<?php declare(strict_types=1);

namespace Dravencms\User\Console;

use Dravencms\User\Acl\AclSchemaMigration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;

final class AclMigrateCommand extends Command
{
    public function __construct(private AclSchemaMigration $migration) { parent::__construct('user:acl:migrate'); }

    protected function configure(): void
    {
        $this->setDescription('Show the identifier-only ACL migration; use --apply to execute it')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Apply the migration, preserving identifiers, IDs and grants');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sql = $this->migration->getSql();
        foreach ($sql as $statement) { $output->writeln($statement . ';', OutputInterface::OUTPUT_RAW); }
        if ($input->getOption('apply')) { $this->migration->apply(); $output->writeln('ACL identifier migration applied.'); }
        elseif ($sql === []) { $output->writeln('ACL schema is already up to date.'); }
        return Command::SUCCESS;
    }
}
