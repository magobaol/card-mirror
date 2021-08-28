<?php

namespace Tests\App;

use App\GoodSyncCommandFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class GoodSyncCommandFactoryTest extends TestCase
{

    public function test_makeAnalyzeCommand()
    {
        $expectedCommand = 'gsync /progress=yes /list-changes=yes /group-log-lines=no job-tmp card-mirror /f1="file:///some-source-dir" /f2="file:///some-target-dir" /dir=ltor /exclude-hidden=yes /exclude=/card-mirror.log /analyze';
        $command = GoodSyncCommandFactory::makeAnalyzeCommand('/some-source-dir', '/some-target-dir');

        $this->assertEquals($expectedCommand, $command);
    }

    public function test_makeSyncCommand()
    {
        $expectedCommand = 'gsync /progress=yes /list-changes=yes /group-log-lines=no job-tmp card-mirror /f1="file:///some-source-dir" /f2="file:///some-target-dir" /dir=ltor /exclude-hidden=yes /exclude=/card-mirror.log /sync';
        $command = GoodSyncCommandFactory::makeSyncCommand('/some-source-dir', '/some-target-dir');

        $this->assertEquals($expectedCommand, $command);
    }

}