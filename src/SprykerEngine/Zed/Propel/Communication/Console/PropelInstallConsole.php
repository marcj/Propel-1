<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace SprykerEngine\Zed\Propel\Communication\Console;

use SprykerFeature\Zed\Console\Business\Model\Console;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PropelInstallConsole extends Console
{

    const ARGUMENT_NO_DIFF = 'no-diff';
    const ARGUMENT_NO_DIFF_DESCRIPTION = 'Runs without propel:diff';

    const COMMAND_NAME = 'propel:install';
    const DESCRIPTION = 'Runs config convert, create database, postgres compatibility, copy schemas, runs Diff, build models and migrate tasks';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription(self::DESCRIPTION);

        $this->addArgument(
            self::ARGUMENT_NO_DIFF,
            InputOption::VALUE_OPTIONAL,
            self::ARGUMENT_NO_DIFF_DESCRIPTION
        );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $diffArgument = $this->input->getArgument(self::ARGUMENT_NO_DIFF);

        $this->runDependingCommand(ConvertConfigConsole::COMMAND_NAME);
        $this->runDependingCommand(CreateDatabaseConsole::COMMAND_NAME);
        $this->runDependingCommand(PostgresqlCompatibilityConsole::COMMAND_NAME);
        $this->runDependingCommand(SchemaCopyConsole::COMMAND_NAME);
        $this->runDependingCommand(BuildModelConsole::COMMAND_NAME);

        if (in_array(self::ARGUMENT_NO_DIFF, $diffArgument) === false) {
            $this->runDependingCommand(DiffConsole::COMMAND_NAME);
        }

        $this->runDependingCommand(MigrateConsole::COMMAND_NAME);
    }

    /**
     * @param string $command
     * @param array $arguments
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function runDependingCommand($command, array $arguments = [])
    {
        $command = $this->getApplication()->find($command);
        $arguments['command'] = $command;
        $input = new ArrayInput($arguments);
        $command->run($input, $this->output);
    }

}
