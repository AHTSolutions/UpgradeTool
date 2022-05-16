<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\MatchingProcess\Processors;

use AHTSolutions\UpgradeTool\Finders\ParentClasses\PhpFileScanner;
use AHTSolutions\UpgradeTool\MatchingProcess\Processors\ExtendedMethods\DataExtractor;

class ExtendedMethods implements ProcessorInterface
{
    /**
     * @var PhpFileScanner
     */
    protected $fileScanner;

    /**
     * @var DataExtractor
     */
    protected $dataExtractor;

    /**
     * @var array
     */
    private $extendedMethods = [];

    /**
     * @param PhpFileScanner|null $fileScanner
     * @param DataExtractor|null $dataExtractor
     */
    public function __construct(
        ?PhpFileScanner $fileScanner = null,
        ?DataExtractor $dataExtractor = null
    ) {
        $this->fileScanner = $fileScanner !== null ? $fileScanner : new PhpFileScanner();
        $this->dataExtractor = $dataExtractor !== null ? $dataExtractor : new DataExtractor();
    }

    /**
     * @inheriDoc
     *
     * @param string $previousFile
     * @param string $currentFile
     */
    public function checkFiles(string $previousFile, string $currentFile): bool
    {
        if ($this->extendedMethods) {
            $fileClasses = $this->fileScanner->getDeclaredClasses($currentFile);
            $usedClass = $fileClasses ? ltrim(reset($fileClasses), '\\') : null;

            if ($usedClass && isset($this->extendedMethods[$usedClass])) {

                foreach ($this->extendedMethods[$usedClass] as $method) {
                    $currentMethodCode = $this->dataExtractor->extractMethodCode($currentFile, $method);
                    $previousMethodCode = $this->dataExtractor->extractMethodCode($previousFile, $method);
                    if (hash('sha1', $currentMethodCode) != hash('sha1', $previousMethodCode)) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @inheriDoc
     *
     * @param ?string $className
     */
    public function setOriginalClassName(?string $className)
    {
        if ($className !== null) {
            $this->extendedMethods = $this->findMethods($className);
        }
    }

    /**
     * @param string $className
     *
     * @return array
     */
    protected function findMethods(string $className): array
    {
        try {
            $origClass = new \ReflectionClass($className);
            $parentClass = $origClass->getParentClass();

            if ($parentClass) {
                $methods = $origClass->getMethods();
                $result = [];

                foreach ($methods as $method) {
                    if (
                        !$method->isAbstract()
                        && $method->class == $origClass->getName()
                        && ($parentMethod = $this->getOriginalParentMethod($parentClass, $method))
                    ) {
                        $result[$parentMethod->class][] = $parentMethod;
                    }
                }

                return $result;
            }
        } catch (\Throwable $ex) {
            //skip
        }

        return [];
    }

    /**
     * @param \ReflectionClass $class
     * @param \ReflectionMethod $method
     *
     * @return \ReflectionMethod|null
     */
    protected function getOriginalParentMethod(\ReflectionClass $class, \ReflectionMethod $method): ?\ReflectionMethod
    {
        try {
            $parentMethod = $class->getMethod($method->getName());

            return $parentMethod->class == $class->getName()
                ? $parentMethod
                : $this->getOriginalParentMethod($class->getParentClass(), $method);
        } catch (\ReflectionException $ex) {
            // skip
        }

        return null;
    }
}
