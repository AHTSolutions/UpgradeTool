<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\Finders\DIConfiguration;

use AHTSolutions\UpgradeTool\Finders\FinderInterface;

class InternalDependencies implements FinderInterface
{
    use ClassNamingTrait;

    public const TYPE = 'internal_dependencies';

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
     */
    public function getUsedClasses(string $vendorName): array
    {
        if ($this->area) {
            $result = [];

            $config = $this->configExtractor->getDiConfig($this->area);

            $arguments = $config['arguments'] ?? [];
            $types = $config['instanceTypes'] ?? [];
            $usedConfiguration = [];
            $searchPattern = $this->prepareSearchPatternByName($vendorName);

            $searchFunction = static function ($source) use ($searchPattern) {
                return (bool) preg_match($searchPattern, $source);
            };

            if (\count($arguments)) {
                $usedConfiguration = array_filter($arguments, static function ($key) use ($searchFunction, $types) {
                    $flag = $searchFunction($key);

                    if (!$flag && isset($types[$key])) {
                        return $searchFunction($types[$key]);
                    }

                    return $flag;
                }, ARRAY_FILTER_USE_KEY);
            }

            foreach ($usedConfiguration as $mainClass => $diConfig) {
                if (is_array($diConfig)) {
                    foreach ($diConfig as $clConfig) {
                        if (!isset($clConfig['_i_'])) {
                            continue;
                        }
                        $mainClass = $this->getCorrectClassName($mainClass, $config);
                        $usedClass = $this->getCorrectClassName($clConfig['_i_'], $config);

                        if ($searchFunction($mainClass) && !$searchFunction($usedClass)) {
                            if (!isset($result[$mainClass])) {
                                $result[$mainClass] = [
                                    self::TYPE => [$this->area => []],
                                ];
                            }
                            $list = &$result[$mainClass][self::TYPE][$this->area];

                            if (!in_array($usedClass, $list, true)) {
                                $list[] = $usedClass;
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
}
