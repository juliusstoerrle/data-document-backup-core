<?php

use DataDocumentBackup\Adapter\Chromium\ChromiumHtmlToPdfConverter;
use DataDocumentBackup\Adapter\Twig\TwigTemplateDocumentGenerator;
use DataDocumentBackup\Contract\Data;
use DataDocumentBackup\Contract\TemplateReference;

describe('TwigTemplateDocumentGenerator', function () {
    $htmlToPdfConverter = new ChromiumHtmlToPdfConverter();

    it('creates PDFs from a Twig template file', function () use ($htmlToPdfConverter) {
        $sut = new TwigTemplateDocumentGenerator(
            $htmlToPdfConverter,
            './tests/.cache',
            ['./tests/Fixtures'],
        );

        $templateReference = new TemplateReference('Twig', 'SimpleTemplate.html');
        $data = new Data(['name' => 'Jane Doe']);

        $tmpFile = $sut->generateFromTemplateWith($data, $templateReference);
        expect($tmpFile)->toBeFile('A document generator must return a temporary file')
            ->and(filesize($tmpFile))->toBeBetween(12000, 13000, 'Output file should have reasonable file size')
        ;
        unlink($tmpFile);
    });

    it('creates PDFs from the Twig base template', function () use ($htmlToPdfConverter) {
        $sut = new TwigTemplateDocumentGenerator(
            $htmlToPdfConverter,
            './tests/.cache',
            [],
        );

        $templateReference = new TemplateReference(
            'Twig',
            'base.twig',
            ['template' => <<<EOD
<b>Some Heading</b>

{{ name }}
EOD]
        );
        $data = new Data(['name' => 'Jane Doe']);

        $tmpFile = $sut->generateFromTemplateWith($data, $templateReference);
        expect($tmpFile)->toBeFile('A document generator must return a temp file')
            ->and(filesize($tmpFile))->toBeBetween(12000, 13000, 'Output file should have reasonable file size')
            ->and(mime_content_type($tmpFile))->toEqual('application/pdf')
        ;
        unlink($tmpFile);
    });
})->group('requires-chromium');
