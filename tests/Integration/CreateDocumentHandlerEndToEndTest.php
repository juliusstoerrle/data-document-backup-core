<?php

use DataDocumentBackup\Adapter\Flysystem\FlysystemBackupFilesystemFactory;
use DataDocumentBackup\Contract\BackupTarget;
use DataDocumentBackup\Contract\CreateDocument;
use DataDocumentBackup\Contract\Data;
use DataDocumentBackup\Contract\TemplateReference;
use DataDocumentBackup\Core\CreateDocumentHandler;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

describe('CreateDocumentHandler (e2e)', function () {
    it('can create document from word template in local storage', function () {
        \PhpOffice\PhpWord\Settings::setTempDir(__DIR__);
        $sut = new \DataDocumentBackup\Core\CreateDocumentHandler(
            new \DataDocumentBackup\Adapter\PHPOffice\PhpWordTemplateDocumentGenerator(),
            new \DataDocumentBackup\Adapter\Flysystem\FlysystemBackupFilesystemFactory(),
        );

        $cmd = new CreateDocument(
            new TemplateReference('WordTemplate', './tests/Fixtures/SimpleTemplate.docx'),
            new Data(['name' => 'Jane Doe']),
            localBackupTarget('TestOutput_JaneDoe_FunctionalTest.pdf'),
        );

        $sut($cmd);

        // Assert:
        expectLocalOutputFileToExist('TestOutput_JaneDoe_FunctionalTest.pdf')
            ->and(filesize('./tests/.dist/TestOutput_JaneDoe_FunctionalTest.pdf'))
            ->toBeBetween(28000, 30000);

        // Cleanup:
        removeLocalOutputFile('TestOutput_JaneDoe_FunctionalTest.pdf');
    });

    it('handles CreateDocument commands', function () {
        $sut = new CreateDocumentHandler(
            new \DataDocumentBackup\Adapter\PHPOffice\PhpWordTemplateDocumentGenerator(),
            new FlysystemBackupFilesystemFactory(),
        );

        $cmd = new CreateDocument(
            new TemplateReference('WordTemplate', './tests/Fixtures/SimpleTemplate.docx'),
            new Data(['name' => 'Jane Doe']),
            localBackupTarget('TestOutput_JaneDoe.pdf'),
        );

        $sut($cmd);

        // Assert:
        expect('./tests/.dist/TestOutput_JaneDoe.pdf')->toBeFile();

        // Cleanup:
        removeLocalOutputFile('TestOutput_JaneDoe.pdf');
    });

    it('can create document from word template in WebDAV storage', function () {
        \PhpOffice\PhpWord\Settings::setTempDir(__DIR__);
        $sut = new \DataDocumentBackup\Core\CreateDocumentHandler(
            new \DataDocumentBackup\Adapter\PHPOffice\PhpWordTemplateDocumentGenerator(),
            new \DataDocumentBackup\Adapter\Flysystem\FlysystemBackupFilesystemFactory(),
        );

        $webDavConfig = integrationEnvWebDavConfig();

        $cmd = new CreateDocument(
            new TemplateReference('WordTemplate', './tests/Fixtures/SimpleTemplate.docx'),
            new Data(['name' => 'Jane Doe']),
            new BackupTarget('janedoe2.pdf', 'WebDAV', $webDavConfig)
        );

        $sut($cmd);

        // Assert:
        $client = new Client($webDavConfig);
        $adapter = new WebDAVAdapter($client);
        expect($adapter->fileExists('janedoe2.pdf'))->toBeTrue('File not stored in WebDAV folder');

        // Clean Up:
        $adapter->delete('janedoe2.pdf');
    });
});
