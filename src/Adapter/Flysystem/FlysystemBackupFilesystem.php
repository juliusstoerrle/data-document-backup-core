<?php

namespace DataDocumentBackup\Adapter\Flysystem;

use DataDocumentBackup\Exceptions\SavingDocumentFailed;
use DataDocumentBackup\Port\BackupFilesystem;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;

readonly class FlysystemBackupFilesystem implements BackupFilesystem
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    public function storeTmpFileWithNameAs(string $newFilename, string $tmpFilename): void
    {
        try {
            $tmpFile = fopen($tmpFilename, 'r');
            $this->storeTmpFileAs($newFilename, $tmpFile);
        } catch (SavingDocumentFailed $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new SavingDocumentFailed('Failed to transfer tmp file to target storage.', 0, $exception);
        } finally {
            if (is_resource($tmpFile)) {
                fclose($tmpFile);
            }
        }
    }

    public function storeTmpFileAs(string $newFilename, mixed $tmpFile): void
    {
        try {
            $this->filesystem->writeStream($newFilename, $tmpFile);
        } catch (FilesystemException | UnableToWriteFile $exception) {
            var_dump($exception);
            throw new SavingDocumentFailed('Failed to transfer tmp file to target storage.', 0, $exception);
        }
    }
}