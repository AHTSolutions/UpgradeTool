<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\Finders\DIConfiguration;

trait ClassNamingTrait
{
    /**
     * @param string $source
     * @param array $config
     *
     * @return string
     */
    protected function getCorrectClassName(string $source, array $config): string
    {
        $result = $config['instanceTypes'][$source] ?? $source;
        $prefRes = $config['preferences'][$result] ?? $result;

        return $prefRes !== $result && ($result . '\\Interceptor') !== $prefRes
            ? $prefRes
            : $result;
    }
}
