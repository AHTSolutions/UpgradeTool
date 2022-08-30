<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool;

use AHTSolutions\UpgradeTool\DataSaver\SaverFactory;
use Exception;

class DataSaver
{
    protected DataSaver\SaverInterface $saver;

    private string $resultFilePath;

    /**
     * @param string $resultFilePath
     * @param string $saverType
     * @param SaverFactory|null $saverFactory
     * @throws Exception
     */
    public function __construct(
        string $resultFilePath,
        string $saverType,
        ?SaverFactory $saverFactory = null
    ) {
        $this->resultFilePath = $resultFilePath;
        if ($saverFactory === null) {
            $saverFactory = new SaverFactory();
        }
        $this->saver = $saverFactory->create($saverType);
    }

    /**
     * @param array $data
     * @return void
     * @throws Exception
     */
    public function saveDataToFile(array $data): void
    {
        $f = $this->createFile();
        foreach ($data as $className => $infoList) {
            $this->saver->setOriginClassName($className);
            foreach ($infoList as $dataObj) {
                $this->saver->saveInfoToFile($f, $dataObj);
            }
        }
        fclose($f);
    }

    /**
     * @return resource
     * @throws Exception
     */
    protected function createFile()
    {
        $f = fopen($this->resultFilePath, 'wb+');
        if (!$f) {
            throw new Exception('Can not create file with this path: '. $this->resultFilePath);
        }
        return $f;

    }
}
