<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\DataSaver;

class SaverFactory
{
    /**
     * @var string|null
     */
    private $compareCommand;

    /**
     * @var string[]
     */
    private $typeMap = [
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
     * @throws \Exception
     */
    public function create(string $type): SaverInterface
    {
        if (isset($this->typeMap[$type])) {
            return new $this->typeMap[$type]($this->compareCommand);
        }
        throw new \Exception('Incorrect type!');
    }
}
