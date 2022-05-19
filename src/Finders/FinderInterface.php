<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\Finders;

interface FinderInterface
{
    /**
     * @param string $vendorName
     * @return array
     */
    public function getUsedClasses(string $vendorName): array;

    /**
     * @param string $code
     * @return $this
     */
    public function setAreaCode(string $code): self;
}
