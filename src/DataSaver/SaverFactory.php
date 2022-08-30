<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\DataSaver;

use Exception;
use RuntimeException;

class SaverFactory
{
    private ?string $compareCommand;

    /**
     * @var string[]
     */
    private array $typeMap = [
        TxtSaver::TYPE => TxtSaver::class
    ];

    /**
     * @param string|null $compareCommand
     */
    public function __construct(?string $compareCommand = 'diff')
    {
        $this->compareCommand = $compareCommand;
    }

    /**
     * @param string $type
     * @return SaverInterface
     * @throws Exception
     */
    public function create(string $type): SaverInterface
    {
        if (isset($this->typeMap[$type])) {
            return new $this->typeMap[$type]($this->compareCommand);
        }
        throw new RuntimeException('Incorrect type!');
    }
}
