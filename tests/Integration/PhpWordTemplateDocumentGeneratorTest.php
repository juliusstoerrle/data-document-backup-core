<?php

use DataDocumentBackup\Contract\Data;
use DataDocumentBackup\Contract\TemplateReference;

describe('PhpWordTemplateDocumentGenerator', function () {
    it('creates PDFs from a simple MSWord template', function () {
        $sut = new \DataDocumentBackup\Adapter\PHPOffice\PhpWordTemplateDocumentGenerator();

        $templateReference = new TemplateReference('WordTemplate', './tests/Fixtures/SimpleTemplate.docx');
        $data = new Data(['name' => 'Jane Doe']);

        $tmpFile = $sut->generateFromTemplateWith($data, $templateReference);
        expect($tmpFile)->toBeResource('A document generator must return a resource pointer');
    });
});
