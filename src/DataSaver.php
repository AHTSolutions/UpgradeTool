<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool;

use AHTSolutions\UpgradeTool\DataSaver\SaverFactory;

class DataSaver
{
    /**
     * @var DataSaver\SaverInterface
     */
    protected $saver;

    /**
     * @var string
     */
    private $resultFilePath;

    /**
     * @param SaverFactory|null $saverFactory
     * @param string $resultFilePath
     * @param string $saverType
     * @throws \Exception
     */
    public function __construct(
        ?SaverFactory $saverFactory = null,
        string $resultFilePath,
        string $saverType
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
     * @throws \Exception
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
     * @throws \Exception
     */
    protected function createFile()
    {
        $f = fopen($this->resultFilePath, 'w+');
        if (!$f) {
            throw new \Exception('Can not create file with this path: '. $this->resultFilePath);
        }
        return $f;

    }
}
