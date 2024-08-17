<?php

namespace DataDocumentBackup\Port;

interface HtmlToPdfConverter
{
    /**
     * Turns the provided HTML document into a pdf file
     *
     * @param string $html
     * @return string the name of the temporary pdf file
     */
    public function createPdfFrom(string $html): string;
}