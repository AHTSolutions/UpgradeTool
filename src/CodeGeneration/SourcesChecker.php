<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\CodeGeneration;

use AHTSolutions\UpgradeTool\CLI\Config;

class SourcesChecker
{
    protected Config $cmConfig;

    protected Filesystem $generationFilesystem;

    /**
     * @param Config $cmConfig
     */
    public function __construct(Config $cmConfig)
    {
        $this->cmConfig = $cmConfig;
        $this->generationFilesystem = new Filesystem();
    }

    /**
     * @return bool
     */
    public function isGeneratedFileExist(): bool
    {
        $result = true;

        foreach ($this->cmConfig->getUsedAreas() as $area) {
            $fileName = $this->generationFilesystem->getMetadataAreaFileName($area);
            $result = $result && $this->generationFilesystem->isFileExist($fileName);
        }

        return $result;
    }
}
