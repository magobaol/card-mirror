<?php

namespace Tests\App\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MirrorCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    public function setUp(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('app:mirror');
        $this->commandTester = new CommandTester($command);

    }

    public function test_execute_it_should_fail_with_invalid_card_name()
    {
        $this->commandTester->execute([
            'card-name' => 'an-invalid-name-format'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('The card name is not in valid format', $output);
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