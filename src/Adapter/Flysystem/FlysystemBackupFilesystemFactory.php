<?php

namespace DataDocumentBackup\Adapter\Flysystem;

use DataDocumentBackup\Contract\BackupTarget;
use DataDocumentBackup\Port\BackupFilesystem;
use DataDocumentBackup\Port\BackupFilesystemFactory;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

readonly class FlysystemBackupFilesystemFactory implements BackupFilesystemFactory
{
    public function createFor(BackupTarget $backupTarget): BackupFilesystem {
        return match ($backupTarget->storageType)  {
            'Local' => $this->newLocalAdapter($backupTarget->storageConfig),
            'WebDAV' => $this->newWebDAVAdapter($backupTarget->storageConfig),
            'FTP' => $this->newFTPAdapter($backupTarget->storageConfig),
            default => throw new \RuntimeException('Unknown storage type for factory')
        };
    }

    private function newLocalAdapter(array $config): BackupFilesystem
    {
        if (!isset($config['root'])) {
            throw new \RuntimeException('Invalid or empty root path for local adapter provided.');
        }

        $adapter = new LocalFilesystemAdapter($config['root']);
        return $this->wrapWithFacade($adapter);
    }

    private function newWebDAVAdapter(array $config): BackupFilesystem
    {
        if (!class_exists(Client::class) || !class_exists(WebDAVAdapter::class)) {
            throw new \LogicException('To use the WebDAV storage, install league/flysystem-webdav with all its dependencies.');
        }

        if (!isset($config['baseUri']) || !filter_var($config['baseUri'], FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Invalid or empty baseUri for WebDAV adapter provided.');
        }

        if (!str_ends_with($config['baseUri'], '/')) {
            throw new \RuntimeException('Invalid or empty baseUri for WebDAV adapter provided. Uri must end with a slash.');
        }

        if (empty($config['userName']) || empty($config['password']) ) {
            throw new \RuntimeException('Username or password not provided to WebDAV adapter.');
        }

        $client = new Client($config);
        $adapter = new WebDAVAdapter($client);
        return $this->wrapWithFacade($adapter);
    }

    private function newFTPAdapter(array $config): BackupFilesystem
    {
        if (!class_exists(FtpAdapter::class)) {
            throw new \LogicException('To use the FTP storage, install league/flysystem-ftp.');
        }

        if (!isset($config['host']) || !filter_var($config['host'], FILTER_VALIDATE_DOMAIN)) {
            throw new \RuntimeException('Invalid or empty hostname for FTP adapter provided.');
        }

        if (empty($config['root'])) {
            throw new \RuntimeException('Root path not provided to FTP adapter.');
        }

        if (empty($config['username']) || empty($config['password']) ) {
            throw new \RuntimeException('Username or password not provided to FTP adapter.');
        }


        $adapter = new FtpAdapter(FtpConnectionOptions::fromArray($config));
        return $this->wrapWithFacade($adapter);
    }

    private function wrapWithFacade(FilesystemAdapter $adapter): FlysystemBackupFilesystem
    {
        return new FlysystemBackupFilesystem(new Filesystem($adapter));
    }
}