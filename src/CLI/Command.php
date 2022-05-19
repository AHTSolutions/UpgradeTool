<?php

declare(strict_types=1);

namespace AHTSolutions\UpgradeTool\CLI;

use AHTSolutions\UpgradeTool\CodeGeneration\GenerateCommand;
use AHTSolutions\UpgradeTool\CodeGeneration\SourcesChecker;
use AHTSolutions\UpgradeTool\DataSaver;
use AHTSolutions\UpgradeTool\SearchManager;
use AHTSolutions\UpgradeTool\UpdatingDataProvider;
use Magento\Framework\Autoload\AutoloaderRegistry;
use Symfony\Component\Console\Command\Command as AbstractCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\OutputInterface;

class Command extends AbstractCommand
{
    const NAME = 'check-updates';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SourcesChecker
     */
    protected $sourceChecker;

    /** @var ProgressBar */
    protected $progressBar;

    /**
     * @var GenerateCommand
     */
    protected $generateCommand;

    /**
     * @var SearchManager
     */
    protected $searchManager;

    /**
     * @var UpdatingDataProvider
     */
    protected $upgradingDataProvider;

    /**
     * @var DataSaver
     */
    protected $dataSaver;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Command for searching every critical updates in new magento core code relatively your code');
        $this->config = new Config();
        $this->setDefinition($this->config->initCommandArguments());
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, $output);
        $output->writeln('<info>Checking process was started.</info>');
        $this->progressBar->setMessage('Start processing');
        $this->progressBar->start();
        //1. check generation information (run generation command if needed)
        $flag = $this->sourceChecker->isGeneratedFileExist();
        $this->progressBar->advance();

        if (!$flag) {
            $this->progressBar->setMaxSteps(5);
            $this->progressBar->setMessage('Start code generation process');
            $this->progressBar->display();
            $this->generateCommand->generate();
            $this->progressBar->advance();
        }

        //2. prepare list with classes
        $this->progressBar->setMessage('Searching dependencies');
        $this->progressBar->display();
        $preparedClasses = $this->searchManager->getClassDependencies($this->config->getSearchVendorName());
        $this->progressBar->advance();

        //3. compare depended classes and files
        $this->progressBar->setMessage('Investigating changed classes');
        $this->progressBar->display();
        $result = $this->upgradingDataProvider->findClassChanges($preparedClasses);
        $this->progressBar->advance();

        //4. save and format result
        $this->progressBar->setMessage('Saving data to file');
        $this->progressBar->display();
        $this->dataSaver->saveDataToFile($result);
        $this->progressBar->finish();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    protected function init(InputInterface $input, OutputInterface $output): void
    {
        $this->config->initInputData($input);
        $this->progressBar = new ProgressBar($output, 4);
        $this->progressBar->setFormat('<info>%message%</info> %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%');
        $this->generateCommand = new GenerateCommand(BP);
        $this->sourceChecker = new SourcesChecker($this->config);
        $this->searchManager = new SearchManager(null, $this->config->getUsedAreas());
        $this->upgradingDataProvider = new UpdatingDataProvider(
            AutoloaderRegistry::getAutoloader(),
            $this->config->getPreviousVendorDir(),
            BP
        );
        $saverFactory = new DataSaver\SaverFactory($this->config->getCompareCommand());
        $this->dataSaver = new DataSaver(
            $saverFactory,
            $this->config->getResultFilePath(),
            $this->config->getOutputFormat()
        );
    }
}
