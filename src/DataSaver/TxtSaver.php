<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\DataSaver;

use AHTSolutions\UpgradeTool\Finders\DIConfiguration\ExternalDependencies;
use AHTSolutions\UpgradeTool\Finders\DIConfiguration\InternalDependencies;
use AHTSolutions\UpgradeTool\Finders\ParentClasses;

class TxtSaver implements SaverInterface
{
    const TYPE = 'txt';

    /**
     * @var string
     */
    protected $className;

    /**
     * @var bool
     */
    private $headerAdded = false;

    /**
     * @var string|null
     */
    private $compareCommand;

    /**
     * @var bool
     */
    private $firstLineFlag = false;

    /**
     * @var string[]
     */
    private $typeTemplates = [
        ExternalDependencies::TYPE => "This class '%s' depends from researched class in '%s' area, please check:",
        InternalDependencies::TYPE => "Researched class depends from this class '%s' in '%s' area, please check:",
        ParentClasses::TYPE => "Researched class has changed parent class '%s' in '%s' area, please check:",
    ];

    /**
     * @param string|null $compareCommand
     */
    public function __construct(?string $compareCommand = 'diff')
    {
        $this->compareCommand = $compareCommand;
    }

    /**
     * @param string $className
     *
     * @return SaverInterface
     */
    public function setOriginClassName(string $className): SaverInterface
    {
        $this->className = $className;
        $this->headerAdded = false;

        return $this;
    }

    /**
     * @param $f
     * @param \stdClass $dataObj
     * @return SaverInterface
     */
    public function saveInfoToFile($f, \stdClass $dataObj): SaverInterface
    {
        $lineToSave = $this->formatData($dataObj);

        if (!$this->headerAdded) {
            if ($this->firstLineFlag) {
                fputs($f, "\n\n");
            }
            fputs($f, $this->getHeaderLine());
            $this->headerAdded = true;
            $this->firstLineFlag = true;
        }
        fputs($f, $lineToSave);

        return $this;
    }

    /**
     * @param \stdClass $dataObj
     * @return string
     */
    protected function formatData(\stdClass $dataObj): string
    {
        $message = sprintf($this->typeTemplates[$dataObj->type] ?? '', $dataObj->className, $dataObj->area);

        if ($dataObj->previousFile) {
            return "\t {$message} \n \t\t{$this->compareCommand} {$dataObj->previousFile} {$dataObj->currentFile} \n";
        } elseif ($dataObj->currentFile) {
            return "\t {$message} File '{$dataObj->currentFile}' exists only in new version. \n";
        }

        return "\t {$message} Both files do not exist. \n";
    }

    /**
     * @return string
     */
    protected function getHeaderLine(): string
    {
        return "Researched class '{$this->className}': \n";
    }
}
