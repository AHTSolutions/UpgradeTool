<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool;

use AHTSolutions\UpgradeTool\Finders\FinderInterface;
use AHTSolutions\UpgradeTool\Finders\FindersFactory;

class SearchManager
{
    const MAIN_AREA = 'global';

    /**
     * @var FindersFactory
     */
    protected $findersFactory;

    /**
     * @var string[]
     */
    private $usedAreas = [];

    /**
     * @var FinderInterface[]|null
     */
    private $findersTypeMap;

    /**
     * @param array $areas
     * @param ?FindersFactory $findersFactory
     */
    public function __construct(
        ?FindersFactory $findersFactory = null,
        array $areas = []
    ) {
        $this->usedAreas = $areas;
        $this->findersFactory = $findersFactory ?: new FindersFactory();
    }

    /**
     * @param string $searchPattern
     *
     * @return array
     */
    public function getClassDependencies(string $searchPattern): array
    {
        $result = [];

        foreach ($this->usedAreas as $area) {
            $infoByArea = $this->getDependenciesByArea($searchPattern, $area);
            $result = $this->mergeData($result, $infoByArea);
        }

        return $result;
    }

    /**
     * @param string $searchPattern
     * @param string $code
     *
     * @return array
     */
    protected function getDependenciesByArea(string $searchPattern, string $code): array
    {
        $result = [];

        foreach ($this->getFinders() as $finder) {
            $finder->setAreaCode($code);
            $result = $this->mergeData($result, $finder->getUsedClasses($searchPattern));
        }

        return $result;
    }

    /**
     * @param array $alreadyExistData
     * @param array $newData
     *
     * @return array
     */
    protected function mergeData(array $alreadyExistData, array $newData): array
    {
        foreach ($newData as $class => $info) {
            if (isset($alreadyExistData[$class])) {
                foreach ($info as $type => $areaInfo) {
                    if (isset($alreadyExistData[$class][$type])) {
                        foreach ($areaInfo as $area => $classList) {
                            if ($area !== self::MAIN_AREA) {
                                $classList = \array_diff(
                                    $classList,
                                    $alreadyExistData[$class][$type][self::MAIN_AREA]
                                );
                            }

                            if (isset($alreadyExistData[$class][$type][$area])) {
                                $alreadyExistData[$class][$type][$area] = \array_unique(\array_merge(
                                    $alreadyExistData[$class][$type][$area],
                                    $classList
                                ));
                            } elseif ($classList) {
                                $alreadyExistData[$class][$type][$area] = $classList;
                            }
                        }
                    } else {
                        $alreadyExistData[$class][$type] = $areaInfo;
                    }
                }
            } else {
                $alreadyExistData[$class] = $info;
            }
        }

        return $alreadyExistData;
    }

    /**
     * @return FinderInterface[]
     */
    protected function getFinders(): array
    {
        if ($this->findersTypeMap === null) {
            $this->findersTypeMap = $this->findersFactory->create();
        }

        return $this->findersTypeMap;
    }
}
