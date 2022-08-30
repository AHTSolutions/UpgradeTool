<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\Finders;

use AHTSolutions\UpgradeTool\Finders\ParentClasses\PhpFileScanner;
use Magento\Framework\App\Area;
use Magento\Setup\Module\Di\Code\Scanner\DirectoryScanner;
use ReflectionClass;
use Throwable;
use function array_merge;
use function array_unique;
use function count;
use function ltrim;
use function mb_substr;
use function preg_match;
use function ucfirst;

class ParentClasses implements FinderInterface
{
    public const TYPE = 'parent_classes';

    protected DirectoryScanner $directoryScanner;

    protected PhpFileScanner $phpFileScanner;

    private string $area;

    /**
     * @var string[]
     */
    private array $excludedPatterns = ['#\/Test\/#', '#.*Test\.php$#'];

    /**
     * @var string[]
     */
    private array $dirs = ['/app/code', '/vendor'];

    private string $projectDir;

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
        $this->directoryScanner = $directoryScanner ?? new DirectoryScanner();
        $this->phpFileScanner = $phpFileScanner ?? new PhpFileScanner();
    }

    /**
     * @inheriDoc
     *
     * @param string $vendorName
     *
     * @return array
     */
    public function getUsedClasses(string $vendorName): array
    {
        $result = [];

        if ($this->area === Area::AREA_GLOBAL) {
            $usedFiles = $this->getFiles($vendorName);
            $searchPattern = '/^' . ucfirst($vendorName) . '\\\\.+$/';
            foreach ($usedFiles as $file) {
                $classInfo = $this->getClassesFromFile($file, $searchPattern);

                if ($classInfo) {
                    [$investigatedClass, $depClasses] = $classInfo;

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
     *
     * @return FinderInterface
     */
    public function setAreaCode(string $code): FinderInterface
    {
        $this->area = $code;

        return $this;
    }

    /**
     * @param string $vendorName
     *
     * @return array
     */
    protected function getFiles(string $vendorName): array
    {
        $result = [];

        foreach ($this->dirs as $sourceDir) {
            $files = $this->directoryScanner->scan(
                $this->projectDir . $sourceDir,
                ['php' => $this->prepareSearchPattern($vendorName)],
                $this->excludedPatterns
            );
            $result[] = $files['php'] ?? [];
        }

        return array_merge(...$result);
    }

    /**
     * @param string $file
     * @param string $searchPattern
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
     * @param string $vendorName
     *
     * @return string
     */
    protected function prepareSearchPattern(string $vendorName): string
    {
        $vendorName = ucfirst($vendorName);
        $firstLetter = mb_substr($vendorName, 0, 1);
        $otherPart = mb_substr($vendorName, 1);

        return '#[' . $firstLetter . ',' . mb_strtolower($firstLetter) . ']' . $otherPart . '.*\.php$#';
    }

    /**
     * @param string $class
     * @param string $searchPattern
     *
     * @return string|null
     */
    protected function getParentClassName(string $class, string $searchPattern): ?string
    {
        try {
            $refClass = new ReflectionClass('\\' . $class);
            $parentClass = $refClass->getParentClass();

            if ($parentClass && !preg_match($searchPattern, $parentClass->getName())) {
                return $parentClass->getName();
            }
        } catch (Throwable $e) {
            //skip this class
        }

        return null;
    }
}
