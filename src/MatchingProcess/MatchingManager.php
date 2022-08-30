<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\MatchingProcess;

use AHTSolutions\UpgradeTool\Finders\DIConfiguration\ExternalDependencies;
use AHTSolutions\UpgradeTool\Finders\DIConfiguration\InternalDependencies;
use AHTSolutions\UpgradeTool\Finders\ParentClasses;
use AHTSolutions\UpgradeTool\MatchingProcess\Processors\FileContent;
use AHTSolutions\UpgradeTool\MatchingProcess\Processors\FilesWithoutComments;
use AHTSolutions\UpgradeTool\MatchingProcess\Processors\ExtendedMethods;
use AHTSolutions\UpgradeTool\MatchingProcess\Processors\ProcessorInterface;

class MatchingManager
{
    /**
     * @var ProcessorInterface[]
     */
    private array $checkerListByType;

    /**
     * @var FileContent
     */
    private FileContent $fileContentChecker;

    public function __construct()
    {
        $this->fileContentChecker = new FileContent();
        $fileWithoutComments = new FilesWithoutComments();
        $this->checkerListByType = [
            ExternalDependencies::TYPE => [$this->fileContentChecker, $fileWithoutComments],
            InternalDependencies::TYPE => [$this->fileContentChecker, $fileWithoutComments],
            ParentClasses::TYPE        => [$this->fileContentChecker, $fileWithoutComments, new ExtendedMethods()],
        ];
    }

    /**
     * @param string $previousFile
     * @param string $currentFile
     * @param string $type
     * @param string|null $baseClass
     *
     * @return bool
     */
    public function isFilesEquals(
        string $previousFile,
        string $currentFile,
        string $type,
        ?string $baseClass = null
    ): bool {
        $checkerList = $this->checkerListByType[$type] ?? [$this->fileContentChecker];
        $flag = false;

        foreach ($checkerList as $checker) {
            if (!$flag) {
                $checker->setOriginalClassName($baseClass);
                $flag = $checker->checkFiles($previousFile, $currentFile);
            }
        }

        return $flag;
    }
}
