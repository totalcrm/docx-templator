<?php

namespace TotalCRM\DocxTemplator;

use TotalCRM\DocxTemplator\Document\DocumentInterface;
use DOMDocument;
use ZipArchive;

/**
 * Class Result
 * @package TotalCRM\DocxTemplator
 */
class Result
{
    private DOMDocument $output;
    private DocumentInterface $document;

    /**
     * Result constructor.
     * @param DOMDocument $output
     * @param DocumentInterface $document
     */
    public function __construct(DOMDocument $output, DocumentInterface $document)
    {
        $this->output = $output;
        $this->document = $document;
    }

    /**
     * @return DOMDocument
     */
    public function getContent(): DOMDocument
    {
        return $this->output;
    }

    /**
     * @param null $fileName
     */
    public function download($fileName = null): void
    {
        if ($fileName === null) {
            $fileName = $this->document->getDocumentName();
        }

        $tempFile = $this->buildFile();
        if ($tempFile !== false) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment;filename="' . $fileName . '"');

            // Send file - required ob_clean() & exit;
            if (ob_get_contents()) {
                ob_clean();
            }
            readfile($tempFile);
            unlink($tempFile);
            exit;
        }
    }

    /**
     * @return false|string
     */
    public function buildFile()
    {
        $tempArchive = tempnam(sys_get_temp_dir(), 'doc');
        if (copy($this->document->getDocumentPath(), $tempArchive) === true) {
            $zip = new ZipArchive();
            $zip->open($tempArchive);
            $zip->addFromString($this->document->getContentPath(), $this->output->saveXML());
            $zip->close();

            return $tempArchive;
        }

        return false;
    }

    /**
     * @param string $destinationPath Destination dir with no trailing slash.
     * @param string|null $fileName File name, use original document name if no value present.
     * @return bool
     */
    public function save($destinationPath, $fileName = null): bool
    {
        if ($fileName === null) {
            $fileName = $this->document->getDocumentName();
        }

        $tempFile = $this->buildFile();
        if ($tempFile !== false) {
            $result = copy($tempFile, $destinationPath . '/' . $fileName);
            unlink($tempFile);
            return $result;
        }

        return false;
    }

    /**
     * @return string|false File content or false on error.
     */
    public function output()
    {
        $tempFile = $this->buildFile();
        if ($tempFile !== false) {
            $output = file_get_contents($tempFile);
            unlink($tempFile);

            return $output;
        }

        return false;
    }
} 
