<?php

namespace App\Command;

use App\CardHelper;
use Model\FileOperation;
use App\GoodSyncCommandFactory;
use App\MIOperations;
use App\Operations;
use Model\OperationLogger;
use phpDocumentor\Reflection\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:download-pictures',
    description: 'Add a short description for your command',
)]
class DownloadPicturesCommand extends Command
{
    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;
    private Filesystem $fs;
    private CardHelper $cardHelper;
    private SymfonyStyle $io;
    private $questionHelper;
    private InputInterface $input;
    private OutputInterface $output;

    public function __construct(ParameterBagInterface $parameterBag, Filesystem $filesystem, CardHelper $cardHelper)
    {
        parent::__construct();
        $this->parameterBag = $parameterBag;
        $this->fs = $filesystem;
        $this->cardHelper = $cardHelper;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('card-name', InputArgument::REQUIRED, 'The name of the card you want to mirror')
            ->addOption('base-dir', '', InputOption::VALUE_REQUIRED)
        ;
    }

    private function checkRequiredDir($requiredDir): bool
    {
        if (!$this->fs->exists($requiredDir)) {
            $question = new ConfirmationQuestion(sprintf('The base dir %s does not exist. Should I create it? (Y/n) ', $requiredDir), true);
            if ($this->questionHelper->ask($this->input, $this->output, $question)) {
                $this->fs->mkdir($requiredDir);
                $this->io->writeln('Base dir created');
                return true;
            } else {
                $this->io->error("Then there's nothing I can do here. Bye");
                return false;
            }
        }
        return true;
    }

    private function copy($sourceFile, $targetFile, $dryRun) {
        if (!$dryRun) {
            $this->fs->copy($sourceFile, $targetFile);
        }
    }

    /**
     * @param string $sourceDir
     * @param string $targetMirrorDir
     * @param string $targetImportDir
     */
    protected function mirrorAndImportOperation(string $sourceDir, string $targetMirrorDir, string $targetImportDir, bool $dryRun): Operations
    {
        $operations = new Operations();

        $finder = new Finder();
        $finder->in([$sourceDir])->name('*.*');
        foreach ($finder as $file) {
            $sourceFile = $sourceDir . '/' . $file->getRelativePathname();
            $targetMirrorFile = $targetMirrorDir . '/' . $file->getRelativePathname();

            if ($this->fs->exists($targetMirrorFile)) {
                //$this->io->write('SKIP: ');
                $operations->addMirrorOperation(FileOperation::createSkip($sourceFile, $targetMirrorFile));
            } else {
                //$this->io->write('COPY: ');
                //$this->copy($sourceFile, $targetMirrorFile, $dryRun);
                $operations->addMirrorOperation(FileOperation::createCopy($sourceFile, $targetMirrorFile));

                if (in_array($file->getExtension(), ['ARW', 'arw', 'jpg', 'JPG', 'MP4', 'mp4'])) {
                    $targetImportFile = $targetImportDir . '/' . $file->getFilename();
                    //$this->copy($sourceFile, $targetImportFile, $dryRun);
                    $operations->addImportOperation(FileOperation::createCopy($sourceFile, $targetImportFile));
                }
            }
            //$this->io->writeln($file->getRelativePathname() . ' FROM ' . $sourceDir . ' TO ' . $targetMirrorDir);
        }
        return $operations;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->questionHelper = $this->getHelper('question');
        $this->input = $input;
        $this->output = $output;

        $cardName = $input->getArgument('card-name');

        if (!$this->cardHelper->isValidCardName($cardName)) {
            $this->io->error(sprintf('The card name is not in valid format. It should be named something like %s', $this->cardHelper->getSampleCardName()));
            return Command::INVALID;
        }

        $sourceDir = $this->cardHelper->getSourceDirFromCardName($cardName);

        //***** Check if the source dir exists (that is, the card is inserted) *****
        if (!$this->fs->exists($sourceDir)) {
            $this->io->error(sprintf("The source dir %s does not exists, so probably the card is not inserted", $sourceDir));
            return Command::FAILURE;
        }

        if ($input->getOption('base-dir')) {
            $mirrorBaseDir = $input->getOption('base-dir');
        } else {
            $mirrorBaseDir = $this->parameterBag->get('app.mirror_base_dir');
        }

        if (!$this->checkRequiredDir($mirrorBaseDir)) {
            return Command::FAILURE;
        }

        $importBaseDir = $this->parameterBag->get('app.import_base_dir');
        if (!$this->checkRequiredDir($importBaseDir)) {
            return Command::FAILURE;
        }

        $targetMirrorDir = $this->cardHelper->getTargetDirFromBaseDirAndCardName($mirrorBaseDir, $cardName);
        if (!$this->checkRequiredDir($targetMirrorDir)) {
            return Command::FAILURE;
        }

        $targetImportDir = $this->cardHelper->getTargetDirFromBaseDirAndCardName($importBaseDir, $cardName);
        if (!$this->checkRequiredDir($targetImportDir)) {
            return Command::FAILURE;
        }

        $this->io->writeln('');
        $this->io->writeln("========= Mirror and copy - PREVIEW START ========= ");
        $this->io->writeln('');
        ///$operations = $this->mirrorAndImportOperation($sourceDir, $targetMirrorDir, $targetImportDir, true);

        $operations = new MIOperations(new Finder(), new Filesystem(), $sourceDir, $targetMirrorDir, $targetImportDir);

        foreach ($operations->getOperations() as $operation) {
            $this->io->writeln($operation->toString());
        }

        $this->io->writeln('');
        $this->io->writeln("========= Mirror and copy - SUMMARY ========= ");

        $this->io->writeln(sprintf('Total files on card: %s', $operations->countAll()));
        $this->io->writeln(sprintf('Total files to skip: %s', $operations->countSkip()));
        $this->io->writeln(sprintf('Total new files to mirror: %s', $operations->countMirror()));
        $this->io->writeln(sprintf('Total new files to mirror and import: %s', $operations->countMirrorAndImport()));
        $this->io->writeln('');
        $this->io->writeln("========= Mirror and copy - PREVIEW END ========= ");
        $this->io->writeln('');

        $question = new ConfirmationQuestion('Should I continue? (Y/n) ', true);
        if (!$this->questionHelper->ask($input, $output, $question)) {
            $this->io->writeln("Ok, bye");
            return Command::SUCCESS;
        }

        $this->io->writeln('Ok, hold on, this may take a while...');
        //$this->mirrorAndImportOperation($sourceDir, $targetMirrorDir, $targetImportDir, false);

        $copyOperations = $operations->getActualCopyOperations();
        foreach ($copyOperations as $operation) {
            if ($operation->isCopy()) {
                $this->io->writeln($operation->toString());
                $this->fs->copy($operation->getSourceFile(), $operation->getTargetFile());
            }
        }

        return Command::SUCCESS;
    }


}
