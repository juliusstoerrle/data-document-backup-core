<?php

use DataDocumentBackup\Adapter\Chromium\ChromiumHtmlToPdfConverter;

describe('ChromiumHtmlToPdfConverter', function () {
    it('can convert HTML to PDF saved in tmp file', function () {
        $sut = new ChromiumHtmlToPdfConverter();
        $tmpFile = $sut->createPdfFrom('<b>Test</b>');

        /* an actual comparison would require parsing the content, hashes vary on every execution. For now, check the file has the expected size. */
        expect(filesize($tmpFile))->toEqual(6460);
    });
})->group('requires-chromium');