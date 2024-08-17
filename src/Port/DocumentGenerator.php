<?php

namespace DataDocumentBackup\Port;

use DataDocumentBackup\Contract\Data;
use DataDocumentBackup\Contract\TemplateReference;

interface DocumentGenerator
{
    /**
     * Create an instance of the passed template with the provided data
     *
     * @return resource temporary file pointer (used as tmpfiles might be deleted too early)
     */
    public function generateFromTemplateWith(Data $data, TemplateReference $templateReference): mixed;
}