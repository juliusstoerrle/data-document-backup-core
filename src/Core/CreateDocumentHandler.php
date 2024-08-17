<?php

namespace DataDocumentBackup\Core;

use DataDocumentBackup\Contract\CreateDocument;
use DataDocumentBackup\Port\BackupFilesystemFactory;
use DataDocumentBackup\Port\DocumentGenerator;
use Psr\Log\LoggerInterface;

readonly class CreateDocumentHandler
{
    public function __construct(
        private DocumentGenerator       $documentGenerator, // todo use factory
        private BackupFilesystemFactory $filesystemFactory,
        private ?LoggerInterface        $logger = null,
    ) {
    }

    public function __invoke(CreateDocument $cmd): void
    {
        try {
            $tmpFile = $this->documentGenerator->generateFromTemplateWith($cmd->data, $cmd->templateReference);
            $this->logger?->debug('[DataDocumentBackup] Generated document', ['path' => $tmpFile]);
            $filesystem = $this->filesystemFactory->createFor($cmd->backupTarget);
            $filesystem->storeTmpFileAs($cmd->backupTarget->filename, $tmpFile);
            $this->logger?->debug('[DataDocumentBackup] Permanently stored document');
        } catch (\Exception $exception) {
            $this->logger?->error('[DataDocumentBackup] Failed to generate and store document. ('. $exception->getMessage() . $exception->getFile() . $exception->getLine() .')');
            throw new \RuntimeException('Failed to store data in backup document', 0, $exception);
        } finally {
            if (isset($tmpFile) && is_resource($tmpFile)) {
                fclose($tmpFile);
            }
        }
    }
}