<?php

namespace DataDocumentBackup\Port;

use DataDocumentBackup\Contract\BackupTarget;

interface BackupFilesystemFactory
{
    public function createFor(BackupTarget $backupTarget): BackupFilesystem;
}