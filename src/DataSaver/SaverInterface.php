<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\DataSaver;

use stdClass;

interface SaverInterface
{
    /**
     * @param string $className
     * @return $this
     */
    public function setOriginClassName(string $className): self;

    /**
     * @param $f
     * @param stdClass $dataObj
     * @return $this
     */
    public function saveInfoToFile($f, stdClass $dataObj): self;



}
