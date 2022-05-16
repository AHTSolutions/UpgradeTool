<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\Finders;

use AHTSolutions\UpgradeTool\Finders\DIConfiguration\DataExtractor;
use AHTSolutions\UpgradeTool\Finders\DIConfiguration\ExternalDependencies;
use AHTSolutions\UpgradeTool\Finders\DIConfiguration\InternalDependencies;

class FindersFactory
{
    /**
     * @return array
     */
    public function create(): array
    {
        $finders = [];
        $diDataExtractor = new DataExtractor();
        $finders[ExternalDependencies::TYPE] = new ExternalDependencies($diDataExtractor);
        $finders[InternalDependencies::TYPE] = new InternalDependencies($diDataExtractor);
        $finders[ParentClasses::TYPE] = new ParentClasses(BP);

        return $finders;
    }
}
