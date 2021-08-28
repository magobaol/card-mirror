<?php

namespace App\Command;

use App\CardHelper;
use App\GoodSyncCommandFactory;
use Model\CardName;
use Model\CardSourceDir;
use Model\CardTargetDir;
use Model\OperationLogger;
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
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:mirror',
    description: 'Add a short description for your command',
)]
class MirrorCommand extends Command
{
    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;
    private Filesystem $fs;
    private CardHelper $cardHelper;

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

    private function executeGoodSyncProcess(Process $process, SymfonyStyle $io)
    {
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $io->writeln('');
        $io->writeln("========= GoodSync Process Output Start ========= ");
        $io->writeln('');
        $io->write($process->getOutput());
        $io->writeln("========= GoodSync Process Output End ========= ");
        $io->writeln('');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $questionHelper = $this->getHelper('question');

        $cardName = $input->getArgument('card-name');

        if (!$this->cardHelper->isValidCardName($cardName)) {
            $io->error(sprintf('The card name is not in valid format. It should be named something like %s', $this->cardHelper->getSampleCardName()));
            return Command::INVALID;
        }

        $sourceDir = $this->cardHelper->getSourceDirFromCardName($cardName);

        //***** Check if the source dir exists (that is, the card is inserted) *****
        if (!$this->fs->exists($sourceDir)) {
            $io->error(sprintf("The source dir %s does not exists, so probably the card is not inserted", $sourceDir));
            return Command::FAILURE;
        }

        if ($input->getOption('base-dir')) {
            $mirrorBaseDir = $input->getOption('base-dir');
        } else {
            $mirrorBaseDir = $this->parameterBag->get('app.mirror_base_dir');
        }

        //***** Check if the mirror base dir exists and offer to create *****
        if (!$this->fs->exists($mirrorBaseDir)) {
            $question = new ConfirmationQuestion(sprintf('The base dir %s does not exist. Should I create it? (Y/n) ', $mirrorBaseDir), true);
            if ($questionHelper->ask($input, $output, $question)) {
                $this->fs->mkdir($mirrorBaseDir);
                $io->writeln('Base dir created');
            } else {
                $io->error("Then there's nothing I can do here. Bye");
                return Command::FAILURE;
            }
        }

        $targetDir = $this->cardHelper->getTargetDirFromBaseDirAndCardName($mirrorBaseDir, $cardName);

        //***** Check if the final target dir exists and offer to create *****
        if (!$this->fs->exists($targetDir)) {
            $question = new ConfirmationQuestion(sprintf("The card dir %s does not exist, it's likely that you never mirrored this card before. Should I create the dir and continue? (Y/n) ", $targetDir), true);
            if ($questionHelper->ask($input, $output, $question)) {
                $this->fs->mkdir($targetDir);
                $io->writeln('Card dir created');
            } else {
                $io->error("Then there's nothing I can do here. Bye");
                return Command::FAILURE;
            }
        }

        //***** GoodSync Analyze *****
        $io->writeln("I'm about to launch the GoodSync analyze process with the following parameters");
        $io->writeln(sprintf('Source dir: %s', $sourceDir));
        $io->writeln(sprintf('Target dir: %s', $targetDir));

        $question = new ConfirmationQuestion("Should I continue? (Y/n) ", true);
        if (!$questionHelper->ask($input, $output, $question)) {
            $io->writeln("Ok, bye");
            return Command::SUCCESS;
        }

        $operationLogger = new OperationLogger($targetDir.'/card-mirror.log');

        $io->writeln('Ok, hold on, this may take a while...');

        $process = Process::fromShellCommandline(GoodSyncCommandFactory::makeAnalyzeCommand($sourceDir, $targetDir));
        $this->executeGoodSyncProcess($process, $io);
        $operationLogger->logAnalyze();

        //***** GoodSync Sync *****
        $io->warning('This is your last chance to end the process!');
        $question = new ConfirmationQuestion("Should I continue with the actual sync? (Y/n) ", true);
        if (!$questionHelper->ask($input, $output, $question)) {
            $io->writeln("Ok, bye");
            return Command::SUCCESS;
        }

        $io->writeln('Ok, hold on, this may take a little longer...');
        $process = Process::fromShellCommandline(GoodSyncCommandFactory::makeSyncCommand($sourceDir, $targetDir));
        $this->executeGoodSyncProcess($process, $io);
        $operationLogger->logSync();

        $io->success('Done!');

        return Command::SUCCESS;
    }
}
