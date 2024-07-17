<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\CodeGeneration;

use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GenerateCommand
{
    public const COMMAND_NAME = 'setup:di:compile';

    /**
     * @var string
     */
    protected string $projectPath;

    /**
     * @param string $projectPath
     */
    public function __construct(
        string $projectPath = ''
    ) {
        $this->projectPath = $projectPath;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function generate(): void
    {
        $process = new Process([$this->projectPath . DIRECTORY_SEPARATOR . 'bin/magento', self::COMMAND_NAME]);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $pEx) {
            throw new RuntimeException(
        'Can not finish generation command. Please run this command `bin/magento setup:di:compile` manually'
            );
        }
    }
}
