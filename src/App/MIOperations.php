<?php

namespace App;

use Model\MIOperation;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class MIOperations
{
    /**
     * @var MIOperations[]
     */
    private array $operations;

    public function __construct(Finder $finder, Filesystem $fs, string $sourceDir, string $targetMirrorDir, string $targetImportDir)
    {
        $this->operations = [];
        $finder->in([$sourceDir])->name('*.*');

        foreach ($finder as $file) {
            $sourceFile = $sourceDir . '/' . $file->getRelativePathname();
            $targetMirrorFile = $targetMirrorDir . '/' . $file->getRelativePathname();

            $operation = new MIOperation($sourceFile);

            if ($fs->exists($targetMirrorFile)) {
                $operation->skip();
            } else {
                if (in_array($file->getExtension(), ['ARW', 'arw', 'jpg', 'JPG', 'MP4', 'mp4'])) {
                    $targetImportFile = $targetImportDir . '/' . $file->getFilename();
                    $operation->mirrorAndImport($targetMirrorFile, $targetImportFile);
                } else {
                    $operation->mirror($targetMirrorFile);
                }
            }

            $this->operations[] = $operation;
        }
    }

    public function countSkip(): int
    {
        $count = 0;
        /** @var MIOperation $operation */
        foreach ($this->operations as $operation) {
            if ($operation->isSkip())
            {
                $count++;
            }
        }

        return $count;
    }

    public function countMirror(): int
    {
        $count = 0;
        /** @var MIOperation $operation */
        foreach ($this->operations as $operation) {
            if ($operation->isMirror())
            {
                $count++;
            }
        }

        return $count;
    }

    public function countMirrorAndImport(): int
    {
        $count = 0;
        /** @var MIOperation $operation */
        foreach ($this->operations as $operation) {
            if ($operation->isMirrorAndImport())
            {
                $count++;
            }
        }

        return $count;
    }

    public function countAll(): int
    {
        return count($this->operations);
    }

    public function getOperations(): array
    {
        return $this->operations;
    }

    public function getActualCopyOperations(): array
    {
        $copyOperations = [];
        foreach ($this->operations as $operation) {
            $copyOperations = array_merge($copyOperations, $operation->getActualCopyOperations());
        }

        return $copyOperations;
    }
}