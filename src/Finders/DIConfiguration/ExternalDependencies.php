<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\Finders\DIConfiguration;

use AHTSolutions\UpgradeTool\Finders\FinderInterface;

class ExternalDependencies implements FinderInterface
{
    use ClassNamingTrait;

    const TYPE = 'external_dependencies';

    /**
     * @var DataExtractor
     */
    protected $configExtractor;

    /**
     * @var string|null
     */
    private $area;

    /**
     * @param DataExtractor $configExtractor
     */
    public function __construct(DataExtractor $configExtractor)
    {
        $this->configExtractor = $configExtractor;
    }

    /**
     * @inheriDoc
     *
     * @param string $searchPattern
     */
    public function getUsedClasses(string $searchPattern): array
    {
        if ($this->area) {
            $config = $this->configExtractor->getDiConfig($this->area);
            $result = [];

            if ($config) {
                list($instanceTypes, $preferences) = $this->filterConfig($config, $searchPattern);

                $arguments = $config['arguments'] ?? [];

                if (\count($arguments)) {
                    $patternSearchFunc = function ($source) use ($searchPattern) {
                        return (bool) preg_match($searchPattern, $source);
                    };

                    foreach ($arguments as $class => $diConfig) {
                        if (!$patternSearchFunc($class)) {
                            if (is_array($diConfig)) {
                                foreach ($diConfig as $clConfig) {
                                    if (!isset($clConfig['_i_'])) {
                                        continue;
                                    }
                                    $checkedClass = $clConfig['_i_'];
                                    $usedClass = null;

                                    if (isset($instanceTypes[$checkedClass])) {
                                        $usedClass = $instanceTypes[$checkedClass];
                                    } elseif (isset($preferences[$checkedClass])) {
                                        $usedClass = $preferences[$checkedClass];
                                    } elseif ($patternSearchFunc($checkedClass)) {
                                        $usedClass = $checkedClass;
                                    }

                                    if ($usedClass !== null) {
                                        $newUsedClass = $this->getCorrectClassName($usedClass, $config);

                                        if (!$patternSearchFunc($newUsedClass)) {
                                            continue;
                                        }
                                        $usedClass = $newUsedClass;

                                        if (!isset($result[$usedClass])) {
                                            $result[$usedClass] = [
                                                self::TYPE => [$this->area => []],
                                            ];
                                        }
                                        $list = &$result[$usedClass][self::TYPE][$this->area];
                                        $class = $this->getCorrectClassName($class, $config);

                                        if (!$patternSearchFunc($class) && !in_array($class, $list)) {
                                            $list[] = $class;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return $result;
        }

        return [];
    }

    /**
     * @inheriDoc
     *
     * @param string $code
     */
    public function setAreaCode(string $code): self
    {
        $this->area = $code;

        return $this;
    }

    /**
     * @param array $config
     * @param string $searchPattern
     *
     * @return array[]
     */
    protected function filterConfig(array $config, string $searchPattern): array
    {
        $instanceTypes = $config['instanceTypes'] ?? [];
        $instanceTypesResult = [];
        $preference = $config['preferences'] ?? [];
        $preferencesResult = [];
        $patternSearchFunc = function ($source, $pattern) {
            return (bool) preg_match($pattern, $source);
        };

        if (\count($instanceTypes)) {
            $instanceTypesResult = array_filter($instanceTypes, function ($val, $key) use ($searchPattern, $patternSearchFunc) {
                return $patternSearchFunc($val, $searchPattern) && !$patternSearchFunc($key, $searchPattern);
            }, ARRAY_FILTER_USE_BOTH);
        }

        if (\count($preference)) {
            $preferencesResult = array_filter($preference, function ($val, $key) use ($searchPattern, $patternSearchFunc) {
                return $patternSearchFunc($val, $searchPattern)
                    && !$patternSearchFunc($key, '/\\\\.+Interface$/')
                    && !$patternSearchFunc($key, $searchPattern);
            }, ARRAY_FILTER_USE_BOTH);
        }

        return [$instanceTypesResult, $preferencesResult];
    }
}
