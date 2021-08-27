<?php

namespace App\Command;

use Model\CardName;
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

    public function __construct(ParameterBagInterface $parameterBag)
    {
        parent::__construct();
        $this->parameterBag = $parameterBag;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('card-name', InputArgument::REQUIRED, 'The name of the card you want to mirror')
            ->addOption('base-dir', InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /*
         * 1. Check if the volume (card) name passed in input is right-ish (0001, maybe with a prefix)
         * 2. Check if a target card dir already exists. If not, create it.
         * 3. Create the GoodSync Command to launch
         *      - When using a temp job, how the .gsdata folders are handled? It shouldn't be a problem
         * 4. Launch the GoodSync Command to Analyze and save the result in a log file with the target folder with timestamp as name
         * 5. Write the analyze operation in a main log file in the target folder
         * 6. Launch the GoodSync command to execute the sync
         *      - How to display the output in real time?
         * 7. Write the sync operation in a main log file in the target folder
         */

        $io = new SymfonyStyle($input, $output);
        $cardName = $input->getArgument('card-name');

        if (!CardName::isValid($cardName)) {
            $io->error(sprintf('The card name is not in valid format. It should be named something like %s', CardName::getSample()));
            return Command::INVALID;
        }

        if ($input->getOption('base-dir')) {
            $mirrorBaseDir = $input->getOption('base-dir');
        } else {
            $mirrorBaseDir = $this->parameterBag->get('app.mirror_base_dir');
        }

        $fs = new Filesystem();

        $questionHelper = $this->getHelper('question');

        if (!$fs->exists($mirrorBaseDir)) {
            $question = new ConfirmationQuestion(sprintf('The base dir %s does not exist. Should I create it? (y/n) ', $mirrorBaseDir), true);
            if ($questionHelper->ask($input, $output, $question)) {
                $fs->mkdir($mirrorBaseDir);
                $io->writeln('Base dir created');
            } else {
                $io->error("Then there's nothing I can do here. Bye");
                return Command::FAILURE;
            }
        }

        $cardDir = $mirrorBaseDir.'/'.$cardName;
        if (!$fs->exists($cardDir)) {
            $question = new ConfirmationQuestion(sprintf("The card dir %s does not exist, it's likely that you never mirrored this card before. Should I create the dir and continue? (y/n) ", $cardDir), true);
            if ($questionHelper->ask($input, $output, $question)) {
                $fs->mkdir($cardDir);
                $io->writeln('Card dir created');
            } else {
                $io->error("Then there's nothing I can do here. Bye");
                return Command::FAILURE;
            }
        }

        $io->writeln($mirrorBaseDir);

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
