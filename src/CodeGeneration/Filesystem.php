<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\CodeGeneration;

class Filesystem
{
    const GENERATION_DIR = 'generated';
    const GENERATION_META_DIR = 'metadata';

    /**
     * @var string
     */
    protected $generationDir = '';

    /**
     * @param string|null $generationDirPath
     */
    public function __construct(?string $generationDirPath = null)
    {
        $this->generationDir = $generationDirPath ?? BP . DIRECTORY_SEPARATOR . self::GENERATION_DIR;
    }

    /**
     * @param string $areaCode
     *
     * @return string
     */
    public function getMetadataAreaFileName(string $areaCode): string
    {
        return self::GENERATION_META_DIR . DIRECTORY_SEPARATOR . $areaCode . '.php';
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    public function isFileExist(string $filename): bool
    {
        $filePath = $this->getAbsoluteFilePath($filename);

        return \file_exists($filePath);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getAbsoluteFilePath(string $name): string
    {
        return $this->generationDir . DIRECTORY_SEPARATOR . $name;
    }
}
