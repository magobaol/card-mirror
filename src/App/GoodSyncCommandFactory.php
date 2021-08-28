<?php

namespace App;

class GoodSyncCommandFactory
{
    private static function getCommonConfiguration($sourceDir, $targetDir, $jobName): array
    {
        $gs[] = 'gsync';
        $gs[] = '/progress=yes';
        $gs[] = '/list-changes=yes';
        $gs[] = '/group-log-lines=no';
        $gs[] = 'job';
        $gs[] = $jobName;
        $gs[] = sprintf('/f1="file://%s"', $sourceDir);
        $gs[] = sprintf('/f2="file://%s"', $targetDir);
        $gs[] = '/dir=ltor';
        $gs[] = '/exclude-hidden=yes';
        $gs[] = '/exclude=/card-mirror.log';

        return $gs;
    }

    public static function makeAnalyzeCommand(string $sourceDir, string $targetDir, $jobName): string
    {
        $gs = self::getCommonConfiguration($sourceDir, $targetDir, $jobName);
        $gs[] = '/analyze';

        return implode(" ", $gs);
    }

    public static function makeSyncCommand(string $sourceDir, string $targetDir, $jobName)
    {
        $gs = self::getCommonConfiguration($sourceDir, $targetDir, $jobName);
        $gs[] = '/sync';

        return implode(" ", $gs);
    }
}