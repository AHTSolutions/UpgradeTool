<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\Finders\DIConfiguration;

use AHTSolutions\UpgradeTool\Finders\FinderInterface;
use function count;

class ExternalDependencies implements FinderInterface
{
    use ClassNamingTrait;

    public const TYPE = 'external_dependencies';

    protected DataExtractor $configExtractor;

    private ?string $area;

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
     * @param string $vendorName
     *
     * @return array
     */
    public function getUsedClasses(string $vendorName): array
    {
        if ($this->area) {
            $searchPattern = $this->prepareSearchPatternByName($vendorName);
            $config = $this->configExtractor->getDiConfig($this->area);
            $result = [];

            if ($config) {
                [$instanceTypes, $preferences] = $this->filterConfig($config, $searchPattern);

                $arguments = $config['arguments'] ?? [];

                if (count($arguments)) {
                    $patternSearchFunc = static function ($source) use ($searchPattern) {
                        return (bool) preg_match($searchPattern, $source);
                    };

                    foreach ($arguments as $class => $diConfig) {
                        if (is_array($diConfig) && !$patternSearchFunc($class)) {
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

                                    if (!$patternSearchFunc($class) && !in_array($class, $list, true)) {
                                        $list[] = $class;
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
        $patternSearchFunc = static function ($source, $pattern) {
            return (bool) preg_match($pattern, $source);
        };

        if (count($instanceTypes)) {
            $instanceTypesResult = array_filter($instanceTypes, static function ($val, $key) use ($searchPattern, $patternSearchFunc) {
                return $patternSearchFunc($val, $searchPattern) && !$patternSearchFunc($key, $searchPattern);
            }, ARRAY_FILTER_USE_BOTH);
        }

        if (count($preference)) {
            $preferencesResult = array_filter($preference, static function ($val, $key) use ($searchPattern, $patternSearchFunc) {
                return $patternSearchFunc($val, $searchPattern)
                    && !$patternSearchFunc($key, '/\\\\.+Interface$/')
                    && !$patternSearchFunc($key, $searchPattern);
            }, ARRAY_FILTER_USE_BOTH);
        }

        return [$instanceTypesResult, $preferencesResult];
    }
}
