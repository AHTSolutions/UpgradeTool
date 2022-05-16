<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\MatchingProcess\Processors;

interface ProcessorInterface
{
    /**
     * @param string $previousFile
     * @param string $currentFile
     * @return bool
     */
    public function checkFiles(string $previousFile, string $currentFile): bool;

    /**
     * @param string $className
     * @return mixed
     */
    public function setOriginalClassName(?string $className);
}
