<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\CLI;

use Symfony\Component\Console\Application as AbstractApplication;

class Application extends AbstractApplication
{


    public function __construct()
    {
        parent::__construct('mg-upgrade-tool', '1.0.0');
        $this->setDefaultCommand(Command::NAME, true);
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new Command();

        return $commands;
    }
}
