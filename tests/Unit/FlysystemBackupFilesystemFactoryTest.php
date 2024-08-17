<?php

use DataDocumentBackup\Adapter\Flysystem\FlysystemBackupFilesystem;
use DataDocumentBackup\Adapter\Flysystem\FlysystemBackupFilesystemFactory;
use DataDocumentBackup\Contract\BackupTarget;

describe('FlysystemBackupFilesystemFactory', function () {
    it('supports Local storage', function () {
        $backupTarget = new BackupTarget('janedoe.pdf', 'Local', [
            'root' => './tests' , // required
        ]);

        $sut = new FlysystemBackupFilesystemFactory();
        $res = $sut->createFor($backupTarget);

        expect($res)->toBeInstanceOf(FlysystemBackupFilesystem::class);
    });

    it('fails if no root path is provided to local storage', function () {
        $backupTarget = new BackupTarget('janedoe.pdf', 'Local', []);

        $sut = new FlysystemBackupFilesystemFactory();
        expect(fn() => $sut->createFor($backupTarget))->toThrow(RuntimeException::class);
    });

    it('supports WebDAV storage', function () {
        $backupTarget = new BackupTarget('janedoe.pdf', 'WebDAV', [
            'baseUri' => 'https://your-webdav-server.example.org/', // required
            'userName' => 'your_user',
            'password' => 'superSecret1234'
        ]);

        $sut = new FlysystemBackupFilesystemFactory();
        $res = $sut->createFor($backupTarget);

        expect($res)->toBeInstanceOf(FlysystemBackupFilesystem::class);
    });

    it('fails if invalid hostname provided to WebDAV config', function () {
        $backupTarget = new BackupTarget('janedoe.pdf', 'WebDAV', [
            'baseUri' => '00drop'
        ]);

        $sut = new FlysystemBackupFilesystemFactory();
        expect(fn() => $sut->createFor($backupTarget))->toThrow(RuntimeException::class, 'Invalid or empty baseUri for WebDAV adapter provided.');
    });


    it('fails if hostname provided to WebDAV config has no trailing slash', function () {
        $backupTarget = new BackupTarget('janedoe.pdf', 'WebDAV', [
            'baseUri' => 'https://example.com'
        ]);

        $sut = new FlysystemBackupFilesystemFactory();
        expect(fn() => $sut->createFor($backupTarget))->toThrow(RuntimeException::class, 'Invalid or empty baseUri for WebDAV adapter provided. Uri must end with a slash.');
    });

    it('fails if no username and password provided to WebDAV config ', function () {
        $backupTarget = new BackupTarget('janedoe.pdf', 'WebDAV', [
            'baseUri' => 'https://example.com/'
        ]);

        $sut = new FlysystemBackupFilesystemFactory();
        expect(fn() => $sut->createFor($backupTarget))->toThrow(RuntimeException::class, 'Username or password not provided to WebDAV adapter.');
    });

    it('supports FTP storage', function () {
        $backupTarget = new BackupTarget('janedoe.pdf', 'FTP', [
                'host' => 'your-ftp.example.org', // required
                'root' => '/root/path/', // required
                'username' => 'username', // required
                'password' => 'password', // required
                'port' => 21,
                'ssl' => false,
                'timeout' => 90,
        ]);

        $sut = new FlysystemBackupFilesystemFactory();
        $res = $sut->createFor($backupTarget);

        expect($res)->toBeInstanceOf(FlysystemBackupFilesystem::class);
    });

    it('fails if invalid host provided to FTP config ', function () {
        $backupTarget = new BackupTarget('janedoe.pdf', 'FTP', [
            'host' => ''
        ]);

        $sut = new FlysystemBackupFilesystemFactory();
        expect(fn() => $sut->createFor($backupTarget))->toThrow(RuntimeException::class, 'Invalid or empty hostname for FTP adapter provided.');
    });

    it('fails if no root path provided to FTP config ', function () {
        $backupTarget = new BackupTarget('janedoe.pdf', 'FTP', [
            'host' => 'https://example.com',
            'username' => 'username',
            'password' => 'password',
        ]);

        $sut = new FlysystemBackupFilesystemFactory();
        expect(fn() => $sut->createFor($backupTarget))->toThrow(RuntimeException::class, 'Root path not provided to FTP adapter.');
    });

    it('fails if no username and password provided to FTP config ', function () {
        $backupTarget = new BackupTarget('janedoe.pdf', 'FTP', [
            'host' => 'https://example.com',
            'root' => '/path'
        ]);

        $sut = new FlysystemBackupFilesystemFactory();
        expect(fn() => $sut->createFor($backupTarget))->toThrow(RuntimeException::class, 'Username or password not provided to FTP adapter.');
    });
});
