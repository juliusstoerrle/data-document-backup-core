<?php

namespace DataDocumentBackup\Port;

interface BackupFilesystem
{
    /**
     * @param string $newFilename
     * @param resource $tmpFile pointer to the source file
     */
    public function storeTmpFileAs(string $newFilename, mixed $tmpFile): void;
}