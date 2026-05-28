<?php

namespace App;

use Model\FileOperation;

class Operations
{
    /**
     * @var FileOperation[]
     */
    private array $mirrorOperations;
    /**
     * @var FileOperation[]
     */
    private array $importOperations;

    public function __construct()
    {
        $this->mirrorOperations = [];
        $this->importOperations = [];
    }

    public function addMirrorOperation(FileOperation $operation)
    {
        $this->mirrorOperations[] = $operation;
    }

    public function addImportOperation(FileOperation $operation)
    {
        $this->importOperations[] = $operation;
    }

    public function getCountMirrorOperations(): int
    {
        return count($this->mirrorOperations);
    }

    public function getMirrorOperations(): array
    {
        return $this->mirrorOperations;
    }

    public function getCountMirrorCopyOperations(): int
    {
        $count = 0;
        foreach ($this->mirrorOperations as $mirrorOperation) {
            if ($mirrorOperation->isCopy()) {
                $count++;
            }
        }

        return $count;
    }

    public function getCountImportCopyOperations(): int
    {
        $count = 0;
        foreach ($this->importOperations as $importOperation) {
            if ($importOperation->isCopy()) {
                $count++;
            }
        }

        return $count;
    }

}