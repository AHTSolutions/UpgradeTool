<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\Finders;

use AHTSolutions\UpgradeTool\Finders\ParentClasses\PhpFileScanner;
use Magento\Framework\App\Area;
use Magento\Setup\Module\Di\Code\Scanner\DirectoryScanner;

class ParentClasses implements FinderInterface
{
    const TYPE = 'parent_classes';

    /**
     * @var DirectoryScanner
     */
    protected $directoryScanner;

    /**
     * @var PhpFileScanner
     */
    protected $phpFileScanner;

    /**
     * @var string
     */
    private $area;

    /**
     * @var string[]
     */
    private $excludedPatterns = ['#\/Test\/#', '#.*Test\.php$#'];

    /**
     * @var string[]
     */
    private $dirs = ['/app/code', '/vendor'];

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @param string $projectDir
     * @param DirectoryScanner|null $directoryScanner
     * @param PhpFileScanner|null $phpFileScanner
     */
    public function __construct(
        string $projectDir,
        ?DirectoryScanner $directoryScanner = null,
        ?PhpFileScanner $phpFileScanner = null
    ) {
        $this->projectDir = $projectDir;
        $this->directoryScanner = $directoryScanner === null ? new DirectoryScanner() : $directoryScanner;
        $this->phpFileScanner = $phpFileScanner === null ? new PhpFileScanner() : $phpFileScanner;
    }

    /**
     * @inheriDoc
     *
     * @param string $searchPattern
     */
    public function getUsedClasses(string $searchPattern): array
    {
        $result = [];

        if ($this->area === Area::AREA_GLOBAL) {
            $usedFiles = $this->getFiles($searchPattern);

            foreach ($usedFiles as $file) {
                $classInfo = $this->getClassesFromFile($file, $searchPattern);

                if ($classInfo) {
                    list($investigatedClass, $depClasses) = $classInfo;

                    if (!isset($result[$investigatedClass])) {
                        $result[$investigatedClass] = [
                            self::TYPE => [$this->area => []],
                        ];
                    }
                    $list = &$result[$investigatedClass][self::TYPE][$this->area];
                    $list = array_unique(array_merge($list, $depClasses));
                }
            }
        }

        return $result;
    }

    /**
     * @inheriDoc
     *
     * @param string $code
     */
    public function setAreaCode(string $code): FinderInterface
    {
        $this->area = $code;

        return $this;
    }

    /**
     * @param string $searchPattern
     *
     * @return array
     */
    protected function getFiles(string $searchPattern): array
    {
        $result = [];
        $searchPattern = $this->convertClPatternToFlPattern($searchPattern);

        if ($searchPattern) {
            foreach ($this->dirs as $sourceDir) {
                $files = $this->directoryScanner->scan($this->projectDir . $sourceDir, ['php' => $searchPattern], $this->excludedPatterns);
                $result = array_merge($result, $files['php'] ?? []);
            }
        }

        return $result;
    }

    /**
     * @param string $file
     * @param string $searchPattern
     *
     * @throws \ReflectionException
     *
     * @return array|null
     */
    protected function getClassesFromFile(string $file, string $searchPattern): ?array
    {
        $classes = $this->phpFileScanner->getDeclaredClasses($file);

        if ($classes) {
            foreach ($classes as $class) {
                $class = ltrim($class, '\\');

                if (preg_match($searchPattern, $class)) {
                    $parentClasses = [];
                    $parentClass = $class;

                    do {
                        $parentClasses[] = $parentClass;
                        $parentClass = $this->getParentClassName($parentClass, $searchPattern);
                    } while ($parentClass !== null);
                    unset($parentClasses[0]);

                    return count($parentClasses) ? [$class, $parentClasses] : null;
                }
            }
        }

        return null;
    }

    /**
     * @param string $pattern
     *
     * @return string|null
     */
    protected function convertClPatternToFlPattern(string $pattern): ?string
    {
        if (preg_match('#\w+#', $pattern, $matches)) {
            return isset($matches[0]) ? '#' . $matches[0] . '.*\.php$#' : null;
        }

        return null;
    }

    /**
     * @param string $class
     * @param string $searchPattern
     * @return string|null
     */
    protected function getParentClassName(string $class, string $searchPattern): ?string
    {
        try {
            $refClass = new \ReflectionClass('\\' . $class);
            $parentClass = $refClass->getParentClass();

            if ($parentClass && !preg_match($searchPattern, $parentClass->getName())) {
                return $parentClass->getName();
            }
        } catch (\Throwable $e) {
            //skip this class
        }

        return null;
    }
}
