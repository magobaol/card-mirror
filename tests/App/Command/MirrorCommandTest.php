<?php

namespace Tests\App\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class MirrorCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private string $mirrorBaseDir;
    private Filesystem $fs;

    public function setUp(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('app:mirror');
        $this->commandTester = new CommandTester($command);

        $this->mirrorBaseDir = 'tests/Fixtures/execution-env/sd-card-mirror';
        $this->fs = new Filesystem();
    }

    public function test_execute_it_should_fail_with_invalid_card_name()
    {
        $this->commandTester->execute([
            'card-name' => 'an-invalid-name-format'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('The card name is not in valid format', $output);
    }

    public function test_execute_it_should_use_the_env_base_dir_if_not_passed_as_option()
    {
        $this->commandTester->execute([
            'card-name' => 'PIC-0001'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('dir/defined/in/env/file', $output);
    }

    public function test_execute_with_not_existing_mirror_base_dir_it_should_ask_and_create()
    {
        $this->fs->remove($this->mirrorBaseDir);

        $this->commandTester->setInputs(['yes']); //Should I create the base dir?
        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--base-dir' => $this->mirrorBaseDir,
        ]);

        $statusCode = $this->commandTester->getStatusCode();
        $output = $this->commandTester->getDisplay();

        $this->assertDirectoryExists($this->mirrorBaseDir);
        $this->assertStringContainsString('Base dir created', $output);
        $this->assertEquals(Command::SUCCESS, $statusCode);
    }

    public function test_execute_with_not_existing_mirror_base_dir_it_should_ask_and_fail()
    {
        $this->fs->remove($this->mirrorBaseDir);

        $this->commandTester->setInputs(['no']); //Should I create the base dir?
        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--base-dir' => $this->mirrorBaseDir,
        ]);

        $statusCode = $this->commandTester->getStatusCode();

        $this->assertDirectoryDoesNotExist($this->mirrorBaseDir);
        $this->assertEquals(Command::FAILURE, $statusCode);
    }

    public function test_execute_with_not_existing_card_dir_it_should_ask_and_create()
    {
        $cardName = 'PIC-0001';
        $this->fs->remove($this->mirrorBaseDir); //Also removes everything inside
        $this->fs->mkdir($this->mirrorBaseDir);

        $this->commandTester->setInputs(['yes']); //Should I create the card dir?
        $this->commandTester->execute([
            'card-name' => $cardName,
            '--base-dir' => $this->mirrorBaseDir,
        ]);

        $statusCode = $this->commandTester->getStatusCode();
        $output = $this->commandTester->getDisplay();

        $this->assertDirectoryExists($this->mirrorBaseDir.'/'.$cardName);
        $this->assertStringContainsString('Card dir created', $output);
        $this->assertEquals(Command::SUCCESS, $statusCode);
    }

    public function test_execute_with_not_existing_card_dir_it_should_ask_and_fail()
    {
        $cardName = 'PIC-0001';
        $this->fs->remove($this->mirrorBaseDir); //Also removes everything inside
        $this->fs->mkdir($this->mirrorBaseDir);

        $this->commandTester->setInputs(['no']); //Should I create the card dir?
        $this->commandTester->execute([
            'card-name' => $cardName,
            '--base-dir' => $this->mirrorBaseDir,
        ]);

        $statusCode = $this->commandTester->getStatusCode();

        $this->assertDirectoryDoesNotExist($this->mirrorBaseDir.'/'.$cardName);
        $this->assertEquals(Command::FAILURE, $statusCode);
    }

    public function test_execute_ok()
    {
        $this->commandTester->execute([
            'card-name' => 'PIC-0001'
        ]);

        $output = $this->commandTester->getStatusCode();
        $this->assertEquals(Command::SUCCESS, $output);
    }

}