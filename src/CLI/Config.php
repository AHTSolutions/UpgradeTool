<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\CLI;

use AHTSolutions\UpgradeTool\DataSaver\TxtSaver;
use Magento\Framework\App\Area;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class Config
{
    const PREVIOUS_VERSION_VENDOR   = 'previous_vendor';
    const OUTPUT_FORMAT             = 'format';
    const RESULT_FILE_PATH          = 'result_file';
    const SEARCH_VENDOR_NAME        = 'vendor_name';
    const USED_AREAS                = 'used_areas';
    const COMPARE_COMMAND           = 'compare_command';
    const CONFIG_FILE               = 'conf';

    const DEFAULT_AREA = 'all';
    const AREA_LIST = [
        Area::AREA_GLOBAL, Area::AREA_FRONTEND, Area::AREA_ADMINHTML,
        Area::AREA_CRONTAB, Area::AREA_WEBAPI_REST, Area::AREA_WEBAPI_SOAP,
        Area::AREA_GRAPHQL, ];

    /**
     * @var string[]|null
     */
    private $usedAreas;

    /**
     * @var string|null
     */
    private $searchVendorName;

    /**
     * @var string|null
     */
    private $previousVendorDir;

    /**
     * @var string|null
     */
    private $resultFile;

    /**
     * @var string
     */
    private $outputFormat;

    /**
     * @var string
     */
    private $compareCommand;

    /**
     * @return array
     */
    public function initCommandArguments(): array
    {
        return [
            new InputOption(
                self::PREVIOUS_VERSION_VENDOR,
                'd',
                InputOption::VALUE_OPTIONAL,
                'Absolute path for previous version vendor'
            ),
            new InputOption(
                self::RESULT_FILE_PATH,
                'r',
                InputOption::VALUE_OPTIONAL,
                'File path for result file',
            ),
            new InputOption(
                self::OUTPUT_FORMAT,
                'f',
                InputOption::VALUE_OPTIONAL,
                'Output result format',
                TxtSaver::TYPE
            ),
           new InputOption(
               self::SEARCH_VENDOR_NAME,
               'p',
               InputOption::VALUE_OPTIONAL,
               'Vendor name for searching',
           ),
            new InputOption(
                self::COMPARE_COMMAND,
                'm',
                InputOption::VALUE_OPTIONAL,
                'Compare command',
                'diff'
            ),
           new InputOption(
               self::USED_AREAS,
               'a',
               InputOption::VALUE_OPTIONAL,
               'Areas for dependency investigation',
               self::DEFAULT_AREA
           ),
           new InputOption(
               self::CONFIG_FILE,
               'c',
               InputOption::VALUE_OPTIONAL,
               'Config file with all options'
           ),
        ];
    }

    /**
     * @return array
     */
    public function getUsedAreas(): array
    {
        return $this->usedAreas;
    }

    /**
     * @return string
     */
    public function getSearchVendorName(): string
    {
        return $this->searchVendorName;
    }

    /**
     * @return string
     */
    public function getPreviousVendorDir(): string
    {
        return $this->previousVendorDir;
    }

    /**
     * @return string
     */
    public function getCompareCommand(): string
    {
        return $this->compareCommand;
    }

    /**
     * @return string
     */
    public function getResultFilePath(): string
    {
        return $this->resultFile;
    }

    /**
     * @return string
     */
    public function getOutputFormat(): string
    {
        return $this->outputFormat;
    }

    /**
     * @param InputInterface $input
     *
     * @throws \Exception
     *
     * @return void
     */
    public function initInputData(InputInterface $input): void
    {
        $file = $input->getOption(self::CONFIG_FILE);

        if ($file) {
            $this->readConfigurationFile($file);
        }

        if ($input->getOption(self::SEARCH_VENDOR_NAME)) {
            $this->searchVendorName = $input->getOption(self::SEARCH_VENDOR_NAME);
        }

        if ($input->getOption(self::PREVIOUS_VERSION_VENDOR)) {
            $this->previousVendorDir = $input->getOption(self::PREVIOUS_VERSION_VENDOR);
        }

        if ($input->getOption(self::RESULT_FILE_PATH)) {
            $this->resultFile = $input->getOption(self::RESULT_FILE_PATH);
        }

        if ($this->outputFormat === null && $input->getOption(self::OUTPUT_FORMAT)) {
            $this->outputFormat = $input->getOption(self::OUTPUT_FORMAT);
        }

        if ($this->usedAreas === null && $input->getOption(self::USED_AREAS)) {
            $this->usedAreas = $this->convertAreas($input->getOption(self::USED_AREAS));
        }

        if ($this->compareCommand === null && $input->getOption(self::COMPARE_COMMAND)) {
            $this->compareCommand = $input->getOption(self::COMPARE_COMMAND);
        }

        $this->validateConfig();
    }

    /**
     * @param string $fileName
     *
     * @return void
     */
    protected function readConfigurationFile(string $fileName): void
    {
        if ('' !== $fileName && (strspn($fileName, '/\\', 0, 1))) {
            $filePath = file_exists($fileName) ? $fileName : false;
        } else {
            $filePath = realpath($_SERVER['PWD'] . DIRECTORY_SEPARATOR . $fileName);
        }

        if ($filePath) {
            $jsonContentLine = file_get_contents($filePath);
            $data = @json_decode($jsonContentLine, true);

            if (is_array($data) && $data) {
                $this->searchVendorName = $data[self::SEARCH_VENDOR_NAME] ?? null;

                if (isset($data[self::USED_AREAS])) {
                    $this->usedAreas = $this->convertAreas($data[self::USED_AREAS]);
                }
                $this->previousVendorDir = $data[self::PREVIOUS_VERSION_VENDOR] ?? null;
                $this->resultFile = $data[self::RESULT_FILE_PATH] ?? null;
                $this->outputFormat = $data[self::OUTPUT_FORMAT] ?? null;
                $this->compareCommand = $data[self::COMPARE_COMMAND] ?? null;
            }
        }
    }

    /**
     * @param string $info
     *
     * @return array|null
     */
    protected function convertAreas(string $info): ?array
    {
        if ($info) {
            $areas = explode(',', $info);

            if (count($areas) == 1) {
                $singleArea = trim(array_pop($areas));

                if ($singleArea == self::DEFAULT_AREA) {
                    $areas = self::AREA_LIST;
                } elseif (in_array($singleArea, self::AREA_LIST)) {
                    $areas = [$singleArea];
                } else {
                    return null;
                }
            } else {
                $areas = array_intersect($areas, self::AREA_LIST);
            }

            return $areas;
        }

        return null;
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    protected function validateConfig(): void
    {
        if ($this->previousVendorDir === null) {
            throw new \Exception('Please specify a correct previous vendor directory.');
        }

        if (empty($this->searchVendorName)) {
            throw new \Exception('Incorrect vendor name for searching. Please provide correct name.');
        }

        if ($this->usedAreas === null) {
            $this->usedAreas = self::AREA_LIST;
        }
    }
}
