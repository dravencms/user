<?php declare(strict_types=1);

namespace Dravencms\User\Console;

use Dravencms\User\PasswordResetSchemaMigration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;

final class SecurityMigrateCommand extends Command
{
    public function __construct(private PasswordResetSchemaMigration $migration) { parent::__construct('user:security:migrate'); }

    protected function configure(): void
    {
        $this->setDescription('Preview the password-reset digest migration; use --apply to execute it')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Expand the reset digest column to 64 characters');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->migration->getSql() as $sql) { $output->writeln($sql . ';', OutputInterface::OUTPUT_RAW); }
        if ($input->getOption('apply')) { $this->migration->apply(); $output->writeln('Password-reset digest schema is ready.'); }
        return Command::SUCCESS;
    }
}
