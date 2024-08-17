<?php

namespace DataDocumentBackup\Adapter\Chromium;

use DataDocumentBackup\Exceptions\DocumentRenderingFailed;
use DataDocumentBackup\Port\HtmlToPdfConverter;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\FilesystemException;
use HeadlessChromium\Exception\NoResponseAvailable;
use HeadlessChromium\Exception\OperationTimedOut;
use HeadlessChromium\Exception\ScreenshotFailed;
use Symfony\Component\Process\Exception\ProcessStartFailedException;

/**
 * Creates a chromium instance to convert the provided self-contained HTML into a PDF file
 *
 * @todo perf: check potential to keep chromium alive across multiple invocations
 */
final readonly class ChromiumHtmlToPdfConverter implements HtmlToPdfConverter
{
    private string $tmpDir;

    public function __construct(
        private string $chromiumBinary = 'chromium',
        private array $chromiumOptions = ['noSandbox' => true],
        ?string        $tmpDir = null,
        private string $tmpFilePrefix = 'DDB-ChromiumOutput'
    ) {
        $this->tmpDir = $tmpDir ?? (sys_get_temp_dir() . '/ddb');
    }

    /**
     * @inheritDoc
     */
    public function createPdfFrom(string $html): string
    {
        try {
            $browser = $this->getBrowser();
        } catch (ProcessStartFailedException $exception) {
            throw new DocumentRenderingFailed('Chromium binary required for document rendering but browser process could not be started. Check you chromium installation or change your configuration.', 0, $exception);
        }

        try {
            $page = $browser->createPage();
            $page->setHtml($html);
            $tmpFile = $this->createTmpFile();
            $page->pdf()->saveToFile($tmpFile);

            assert(filesize($tmpFile) > 0, 'Chromium output file must exist.');
            return $tmpFile;
        } catch (CommunicationException|ScreenshotFailed|NoResponseAvailable|OperationTimedOut|FilesystemException $e) {
            throw new DocumentRenderingFailed($e->getMessage(), 0, $e);
        } finally {
            $browser->close();
        }
    }

    private function createTmpFile(): string
    {
        $tmpFile = tempnam($this->tmpDir, $this->tmpFilePrefix);

        if (false === $tmpFile) {
            throw new \RuntimeException(sprintf('failed to create temporary file in "%s"', $this->tmpDir));
        }

        return $tmpFile;
    }

    private function getBrowser(): \HeadlessChromium\Browser
    {
        $browserFactory = new BrowserFactory($this->chromiumBinary);
        // starts headless chromium
        return $browserFactory->createBrowser($this->chromiumOptions);
    }
}