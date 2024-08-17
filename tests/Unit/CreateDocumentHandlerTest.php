<?php

use DataDocumentBackup\Contract\BackupTarget;
use DataDocumentBackup\Contract\CreateDocument;
use DataDocumentBackup\Contract\Data;
use DataDocumentBackup\Contract\TemplateReference;
use DataDocumentBackup\Core\CreateDocumentHandler;
use DataDocumentBackup\Exceptions\DocumentRenderingFailed;
use DataDocumentBackup\Port\BackupFilesystemFactory;
use DataDocumentBackup\Port\DocumentGenerator;

describe('CreateDocumentHandler', function () {
    it('captures failures', function () {
        $data = new Data(['name' => 'Jane Doe']);
        $templateReference = new TemplateReference('WordTemplate', './tests/Fixtures/NotExisting.docx');

        $generator = Mockery::mock(DocumentGenerator::class);
        $generator->shouldReceive('generateFromTemplateWith')->with($data, $templateReference)->andThrow(DocumentRenderingFailed::class);

        $sut = new CreateDocumentHandler(
            $generator,
            Mockery::mock(BackupFilesystemFactory::class),
        );

        $cmd = new CreateDocument(
            $templateReference,
            $data,
            new BackupTarget('notgenerated.pdf', 'Local', [
                'root' => './tests',
            ])
        );

        $sut($cmd);
    })->throws(RuntimeException::class);
});
