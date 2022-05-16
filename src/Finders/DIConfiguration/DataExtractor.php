<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\Finders\DIConfiguration;

use AHTSolutions\UpgradeTool\CodeGeneration\Filesystem;

class DataExtractor
{
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * @var array
     */
    private $config = [];

    /**
     * @param Filesystem|null $filesystem
     */
    public function __construct(?Filesystem $filesystem = null)
    {
        $this->fileSystem = $filesystem ?: new Filesystem();
    }

    /**
     * @param string $areCode
     * @return array
     */
    public function getDiConfig(string $areCode): array
    {
        if (!isset($this->config[$areCode])) {
            $metadataFileName = $this->fileSystem->getMetadataAreaFileName($areCode);

            if ($this->fileSystem->isFileExist($metadataFileName)) {
                $filePath = $this->fileSystem->getAbsoluteFilePath($metadataFileName);
                $this->config[$areCode] = require $filePath;
            } else {
                $this->config[$areCode] = [];
            }
        }

        return $this->config[$areCode];
    }
}
