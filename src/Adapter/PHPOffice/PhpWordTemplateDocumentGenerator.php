<?php

namespace DataDocumentBackup\Adapter\PHPOffice;

use DataDocumentBackup\Contract\Data;
use DataDocumentBackup\Contract\TemplateReference;
use DataDocumentBackup\Port\DocumentGenerator;
use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

final class PhpWordTemplateDocumentGenerator implements DocumentGenerator
{
    private string $tmpDir;

    /**
     * @param string $pdfRenderingLibrary must be one of DomPDF, TCPDF, MPDF
     * @param string|null $tmpDir
     * @param string $tmpFilePrefix
     */
    public function __construct(
        private readonly string $pdfRenderingLibrary = Settings::PDF_RENDERER_MPDF,
        ?string $tmpDir = null,
        private readonly string $tmpFilePrefix = 'DDB-PhpWord'
    ) {
        $this->tmpDir = $tmpDir ?? (sys_get_temp_dir() . '/ddb');
    }


    public function generateFromTemplateWith(Data $data, TemplateReference $templateReference): mixed
    {
        $templateProcessor = $this->openTemplate($templateReference);
        $this->replacePlaceholders($templateProcessor, $data);

        try {
            $processedTemplateDocx = $templateProcessor->save();
            $outputFilename = $this->transformToPdf($processedTemplateDocx);

            assert(filesize($outputFilename) > 10000, 'Output file must exist.');
            return fopen($outputFilename, 'r');
        } catch (Exception $e) {
            throw new \RuntimeException('Failed to render template', 0, $e);
        }
    }

    private function openTemplate(TemplateReference $templateReference): TemplateProcessor
    {
        try {
            $templateProcessor = new TemplateProcessor($templateReference->templatePath);
        } catch (CopyFileException|CreateTemporaryFileException $e) {
            throw new \RuntimeException('Failed to copy template', 0, $e);
        }
        return $templateProcessor;
    }

    private function transformToPdf(string $processedTemplateDocx)
    {
        // Set PHPWord config to the injected values
        $pdfRenderingLibraryPath = realpath(realpath(__DIR__ . '../../../vendor/mpdf/mpdf')); // Todo should be configured
        $success = Settings::setPdfRenderer($this->pdfRenderingLibrary, $pdfRenderingLibraryPath);
        assert($success, 'Setting PHP Word pdf rendering options must succeed'); // TODO should be runtime check

        // Load Docx and create output writer
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($processedTemplateDocx);
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');

        // Save to tmp file
        $outputFilename = tempnam($this->tmpDir, $this->tmpFilePrefix);
        $xmlWriter->save($outputFilename);

        return $outputFilename;
    }

    private function replacePlaceholders(TemplateProcessor &$templateProcessor, Data $data): void
    {
        foreach ($data->data as $key => $value) {
            if (is_array($value)) {
                // if the value for the replacement key is an array, this indicates a repeating block
                // in PHPOffice this can be a Block or a Table Row, therefore we try to replace both:
                $templateProcessor->cloneBlock($key, 0, true, false, $value);
                // TODO Fix this (errors if no row macro exists) $templateProcessor->cloneRowAndSetValues($key, $value);
                continue;
            }
            $templateProcessor->setValue($key, $value);
        }
    }
}