<?php

namespace Tests\App\Command;

use App\CardHelper;
use App\Command\MirrorCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class MirrorCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private string $mirrorBaseDir;
    private mixed $filesystem;
    private mixed $cardHelper;

    public function setUp(): void
    {
        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cardHelper = $this->getMockBuilder(CardHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cardHelper->method('getSourceDirFromCardName')->with('PIC-0001')->willReturn('/Volumes/PIC-0001');
        $this->cardHelper->method('isValidCardName')
            ->will($this->returnValueMap([
                ['PIC-0001', true],
                ['an-invalid-name-format', false]
            ]));

        $kernel = static::createKernel();
        $kernel->boot();
        $kernel->getContainer()->set(Filesystem::class, $this->filesystem);
        $kernel->getContainer()->set(CardHelper::class, $this->cardHelper);
        $application = new Application($kernel);

        $command = $application->find('app:mirror');
        $this->commandTester = new CommandTester($command);

        $this->mirrorBaseDir = 'tests/Fixtures/execution-env/sd-card-mirror';
    }

    public function test_execute_with_invalid_card_name_it_should_fail()
    {
        $this->commandTester->execute([
            'card-name' => 'an-invalid-name-format'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('The card name is not in valid format', $output);
    }

    public function test_execute_with_not_existing_source_dir_it_should_fail()
    {
        $this->filesystem->method('exists')->with('/Volumes/PIC-0001')->willReturn(false);

        $this->commandTester->execute([
            'card-name' => 'PIC-0001'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('the card is not inserted', $output);
    }

    public function test_execute_with_no_base_dir_passed_as_option_it_should_use_the_env_base_dir()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                    ['/Volumes/PIC-0001', true],
                    ['dir/defined/in/env/file', false]
            ]));

        $this->commandTester->setInputs([
            'no', //Should I create the base dir?
        ]);

        $this->commandTester->execute([
            'card-name' => 'PIC-0001'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('dir/defined/in/env/file', $output);
    }

    public function test_execute_with_not_existing_mirror_base_dir_it_should_ask_and_create()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                [$this->mirrorBaseDir, false]
            ]));

        $this->commandTester->setInputs([
            'yes', //Should I create the base dir?
            'no',  //Should I create the card dir?
        ]);
        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--base-dir' => $this->mirrorBaseDir,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Base dir created', $output);
    }

    public function test_execute_with_not_existing_mirror_base_dir_it_should_ask_and_fail()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                [$this->mirrorBaseDir, false]
            ]));

        $this->commandTester->setInputs([
            'no' //Should I create the base dir?
        ]);

        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--base-dir' => $this->mirrorBaseDir,
        ]);

        $statusCode = $this->commandTester->getStatusCode();

        $this->assertEquals(Command::FAILURE, $statusCode);
    }

    public function test_execute_with_not_existing_target_dir_it_should_ask_and_create()
    {
        $this->cardHelper
            ->method('getTargetDirFromBaseDirAndCardName')
            ->willReturn($this->mirrorBaseDir.'/PIC-0001');

        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                [$this->mirrorBaseDir, true],
                [$this->mirrorBaseDir.'/PIC-0001', false]
            ]));

        $this->commandTester->setInputs([
            'yes', //Should I create the card dir?
            'no',  //Should I continue with GoodSync analyze?
        ]);
        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--base-dir' => $this->mirrorBaseDir,
        ]);

        $statusCode = $this->commandTester->getStatusCode();
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Card dir created', $output);
        $this->assertEquals(Command::SUCCESS, $statusCode);
    }

    public function test_execute_with_not_existing_target_dir_it_should_ask_and_fail()
    {
        $this->filesystem
            ->method('exists')
            ->will($this->returnValueMap([
                ['/Volumes/PIC-0001', true],
                [$this->mirrorBaseDir, true],
                [$this->mirrorBaseDir.'/PIC-0001', false]
            ]));

        $this->commandTester->setInputs([
            'no', //Should I create the card dir?
        ]);
        $this->commandTester->execute([
            'card-name' => 'PIC-0001',
            '--base-dir' => $this->mirrorBaseDir,
        ]);

        $statusCode = $this->commandTester->getStatusCode();

        $this->assertEquals(Command::FAILURE, $statusCode);
    }

    /*
    public function test_execute_ok()
    {
        $this->commandTester->execute([
            'card-name' => 'PIC-0001'
        ]);

        $output = $this->commandTester->getStatusCode();
        $this->assertEquals(Command::SUCCESS, $output);
    }
    */

}