<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\MatchingProcess;

use Magento\Framework\Autoload\AutoloaderInterface;

class ClassNameCleaner
{
    /**
     * @var AutoloaderInterface
     */
    protected $autoloader;

    /**
     * @var array
     */
    protected $alreadyMappedClasses = [];

    /**
     * @var string[]
     */
    private $cleanPatterns = [
        '/\\\\.+\\\\Interceptor$/' => '/\\\\Interceptor$/',
        '/\\\\.+\\\\Proxy$/' => '/\\\\Proxy$/',
        '/\\\\.+Factory$/' => '/Factory$/',
    ];

    /**
     * @param AutoloaderInterface $autoloader
     */
    public function __construct(AutoloaderInterface $autoloader)
    {
        $this->autoloader = $autoloader;
    }

    /**
     * @param array $classInfo
     *
     * @return array
     */
    public function filterUsedClassNames(array $classInfo): array
    {
        $cleanedData = [];
        $incorrectData = [];

        foreach ($classInfo as $class => $depInfo) {
            $correctName = $this->convertClassName($class);

            if ($correctName) {
                list($correctedList, $notExistClassInfo) = $this->checkClassList($depInfo);
                $cleanedData[$correctName] = $correctedList;
                if ($notExistClassInfo) {
                    $incorrectData[$correctName] = $notExistClassInfo;
                }
            }
        }

        return [$cleanedData, $incorrectData];
    }

    /**
     * @param array $depInfo
     * @return array[]
     */
    protected function checkClassList(array $depInfo): array
    {
        $result = [];
        $incorrect = [];

        foreach ($depInfo as $type => $areInfo) {
            foreach ($areInfo as $area => $classList) {
                foreach ($classList as $className) {
                    $correctClassName = $this->convertClassName($className);

                    if ($correctClassName) {
                        if (!isset($result[$type][$area])) {
                            $result[$type][$area] = [];
                        }
                        $result[$type][$area][] = $correctClassName;
                    } else {
                        if (!isset($incorrect[$type][$area])) {
                            $incorrect[$type][$area] = [];
                        }
                        $incorrect[$type][$area][] = $className;
                    }
                }
            }
        }

        return [$result, $incorrect];
    }

    /**
     * @param string $className
     * @return string|null
     */
    protected function convertClassName(string $className): ?string
    {
        $correctedClassName = $this->alreadyMappedClasses[$className] ?? null;

        if ($correctedClassName === null) {
            $correctedClassName = $className;

            foreach ($this->cleanPatterns as $pattern => $replacePart) {
                if (preg_match($pattern, $correctedClassName)) {
                    $file = $this->autoloader->findFile($correctedClassName);

                    if (!$file || preg_match('/^.+generated\/code\/.*$/', $file)) {
                        $correctedClassName = preg_replace($replacePart, '', $correctedClassName);
                    }
                }
            }
            $correctedClassName = $this->autoloader->findFile($correctedClassName) ? $correctedClassName : false;
            $this->alreadyMappedClasses[$className] = $correctedClassName;
        }

        return $correctedClassName === false ? null : $correctedClassName;
    }
}
