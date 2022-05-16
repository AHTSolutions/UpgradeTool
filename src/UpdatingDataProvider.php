<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool;

use AHTSolutions\UpgradeTool\MatchingProcess\ClassNameCleaner;
use AHTSolutions\UpgradeTool\MatchingProcess\MatchingManager;
use Magento\Framework\Autoload\AutoloaderInterface;

class UpdatingDataProvider
{
    /**
     * @var AutoloaderInterface
     */
    protected $autoloader;

    /**
     * @var ClassNameCleaner
     */
    protected $classNameCleaner;

    /**
     * @var string
     */
    protected $previousVendorDir;

    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var MatchingManager
     */
    protected $matchingManager;

    /**
     * @param AutoloaderInterface $autoloader
     * @param string $previousVendorDir
     * @param string $projectDir
     */
    public function __construct(
        AutoloaderInterface $autoloader,
        string $previousVendorDir,
        string $projectDir
    ) {
        $this->autoloader = $autoloader;
        $this->classNameCleaner = new ClassNameCleaner($autoloader);
        $this->matchingManager = new MatchingManager();
        $this->previousVendorDir = $previousVendorDir;
        $this->projectDir = $projectDir;
    }

    /**
     * @param array $classList
     *
     * @return array
     */
    public function findClassChanges(array $classList): array
    {
        list($preparedData, $notFoundedClasses) = $this->classNameCleaner->filterUsedClassNames($classList);
        $result = [];

        if ($preparedData) {
            foreach ($preparedData as $className => $depInfo) {
                foreach ($depInfo as $type => $areInfo) {
                    foreach ($areInfo as $areaCode => $list) {
                        foreach ($list as $usedClass) {
                            list($currentFile, $previousFile) = $this->prepareFilesByClass($usedClass);

                            if ($previousFile && $currentFile) {
                                $isEquals = $this->matchingManager->isFilesEquals(
                                    $previousFile,
                                    $currentFile,
                                    $type,
                                    $className
                                );

                                if ($isEquals) {
                                    continue;
                                }
                            }
                            $obj = new \stdClass();
                            $obj->type = $type;
                            $obj->area = $areaCode;
                            $obj->className = $usedClass;
                            $obj->previousFile = $previousFile;
                            $obj->currentFile = $currentFile;

                            if (!isset($result[$className])) {
                                $result[$className] = [];
                            }
                            $result[$className][] = $obj;
                        }
                    }
                }
            }
        }

        if ($notFoundedClasses) {
            foreach ($notFoundedClasses as $className => $info) {
                foreach ($info as $type => $areInfo) {
                    foreach ($areInfo as $areaCode => $list) {
                        foreach ($list as $usedClass) {
                            $obj = new \stdClass();
                            $obj->type = $type;
                            $obj->area = $areaCode;
                            $obj->className = $usedClass;
                            $obj->previousFile = null;
                            $obj->currentFile = null;

                            if (!isset($result[$className])) {
                                $result[$className] = [];
                            }
                            $result[$className][] = $obj;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param string $className
     *
     * @return string[]|null[]
     */
    protected function prepareFilesByClass(string $className): array
    {
        $currentFile = $this->autoloader->findFile($className);

        if ($currentFile && file_exists($currentFile)) {
            $currentFile = realpath($currentFile);
            $previousFile = str_replace($this->projectDir . '/vendor', $this->previousVendorDir, $currentFile);
            $previousFile = file_exists($previousFile) ? $previousFile : null;

            return [$currentFile, $previousFile];
        }

        return [null, null];
    }
}
