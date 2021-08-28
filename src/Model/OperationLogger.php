<?php

namespace Model;

class OperationLogger
{
    private string $filename;

    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    private function getLogLine($operation): string
    {
        return sprintf('%s: %s', strftime('%Y-%m-%d %H:%M:%S'), $operation).PHP_EOL;
    }

    private function log($line)
    {
        file_put_contents($this->filename, $line, FILE_APPEND | LOCK_EX);
    }

    public function logAnalyze()
    {
        $this->log($this->getLogLine('Analyze'));
    }

    public function logSync()
    {
        $this->log($this->getLogLine('Sync'));
    }
}