<?php

namespace App;

use Symfony\Component\Process\Process;

class GoodSyncCommandFactory
{
    private static function getCommonConfiguration($sourceDir, $targetDir): array
    {
        $gs[] = 'gsync';
        $gs[] = '/progress=yes';
        $gs[] = '/list-changes=yes';
        $gs[] = '/group-log-lines=no';
        $gs[] = 'job-tmp';
        $gs[] = 'card-mirror';
        $gs[] = sprintf('/f1="file://%s"', $sourceDir);
        $gs[] = sprintf('/f2="file://%s"', $targetDir);
        $gs[] = '/dir=ltor';
        $gs[] = '/exclude-hidden=yes';
        $gs[] = '/exclude=/card-mirror.log';

        return $gs;
    }

    public static function makeAnalyzeCommand(string $sourceDir, string $targetDir): string
    {
        $gs = self::getCommonConfiguration($sourceDir, $targetDir);
        $gs[] = '/analyze';

        return implode(" ", $gs);
    }

    public static function makeSyncCommand(string $sourceDir, string $targetDir)
    {
        $gs = self::getCommonConfiguration($sourceDir, $targetDir);
        $gs[] = '/sync';

        return implode(" ", $gs);
    }
}