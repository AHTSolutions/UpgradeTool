<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\MatchingProcess\Processors;

class FileContent implements ProcessorInterface
{
    /**
     * @inheriDoc
     */
    public function checkFiles(string $previousFile, string $currentFile): bool
    {
        return sha1_file($previousFile) === sha1_file($currentFile);
    }

    /**
     * @inheriDoc
     */
    public function setOriginalClassName(?string $className)
    {
        // not needed
    }
}
