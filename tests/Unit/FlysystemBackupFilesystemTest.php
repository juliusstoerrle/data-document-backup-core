<?php

use DataDocumentBackup\Adapter\Flysystem\FlysystemBackupFilesystem;
use League\Flysystem\Filesystem;

describe('FlysystemBackupFilesystemFactory', function () {
    it('can store file referenced by name', function () {
        $filesystem = new Filesystem(new League\Flysystem\InMemory\InMemoryFilesystemAdapter());
        $sut = new FlysystemBackupFilesystem($filesystem);

        // Create temporary file
        $tmpFile = tempnam(sys_get_temp_dir(),'test');
        $tmpFileR = fopen($tmpFile, 'w');
        fwrite($tmpFileR, 'TestFile');
        fclose($tmpFileR);

        $sut->storeTmpFileWithNameAs('test.txt', $tmpFile);

        expect($filesystem->has('test.txt'))->toBeTrue('Stored file must exist in file system')
            ->and($filesystem->fileSize('test.txt'))->toBeGreaterThan(0);

        unlink($tmpFile);
    });

    it('can store file provided as resource', function () {
        $filesystem = new Filesystem(new League\Flysystem\InMemory\InMemoryFilesystemAdapter());
        $sut = new FlysystemBackupFilesystem($filesystem);

        $tmpFile = tmpfile();
        fwrite($tmpFile, 'TestFile');

        $sut->storeTmpFileAs('test.txt', $tmpFile);

        expect($filesystem->has('test.txt'))->toBeTrue('Stored file must exist in file system')
            ->and($filesystem->fileSize('test.txt'))->toBeGreaterThan(0);

        fclose($tmpFile);
    });
});
