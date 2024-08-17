<?php

namespace DataDocumentBackup\Port;

use DataDocumentBackup\Contract\Data;
use DataDocumentBackup\Contract\TemplateReference;

interface DocumentGeneratorFactory
{
    /**
     * Create an instance of the passed template with the provided data
     *
     * @return string temporary filename
     */
    public function generateFromTemplateWith(Data $data, TemplateReference $templateReference): string;
}