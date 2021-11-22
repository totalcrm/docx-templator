<?php

namespace TotalCRM\DocxTemplator\Document;

use DOMDocument;
use TotalCRM\DocxTemplator\Exception\InvalidArgumentException;
use TotalCRM\DocxTemplator\Processor\Tag;
use ZipArchive;

/**
 * Class Document
 * @package TotalCRM\DocxTemplator\Document
 */
abstract class Document implements DocumentInterface
{
    public const XPATH_PARAGRAPH = 0;
    public const XPATH_RUN = 1;
    public const XPATH_RUN_PROPERTY = 2;
    public const XPATH_TEXT = 3;

    private string $documentName;
    private string $documentPath;

    /**
     * Document constructor.
     * @param $documentPath
     * @throws InvalidArgumentException
     */
    public function __construct($documentPath)
    {
        if (file_exists($documentPath) === false) {
            throw new InvalidArgumentException('File not found.');
        }

        $this->documentPath = $documentPath;
        $this->documentName = pathinfo($this->documentPath, PATHINFO_BASENAME);
    }

    /**
     * Extract main content file.
     *
     * @param string $to Path to extract content file.
     * @param bool $overwrite Overwrite content file.
     * @return string Full path to extracted document file.
     * @throws InvalidArgumentException
     */
    public function extract($to, $overwrite): string
    {
        $filePath = $to . $this->getDocumentName() . '/' . $this->getContentPath();

        if ($overwrite === true || !file_exists($filePath)) {
            $zip = new ZipArchive();

            $code = $zip->open($this->getDocumentPath());
            if ($code !== true) {
                throw new InvalidArgumentException(
                    'Can`t open archive "' . $this->documentPath . '", code "' . $code . '" returned.'
                );
            }

            if ($zip->extractTo($to . $this->documentName, $this->getContentPath()) === false) {
                throw new InvalidArgumentException('Destination not reachable.');
            }
        }

        return $filePath;
    }

    /**
     * @return false|string
     */
    public function getDocumentHash()
    {
        return md5_file($this->documentPath);
    }

    /**
     * @return string|null
     */
    public function getDocumentName()
    {
        return $this->documentName;
    }

    /**
     * @return string|null
     */
    public function getDocumentPath()
    {
        return $this->documentPath;
    }

    /**
     * @param DOMDocument $template
     */
    abstract public function cleanup(DOMDocument $template);

    /**
     * @return string
     */
    abstract public function getContentPath();

    /**
     * @return string
     */
    abstract public function getNodePath();

    /**
     * @param int $type XPATH_* constant.
     * @param bool $global Append global xpath //.
     */
    abstract public function getNodeName($type, $global = false);

    /**
     * @param string $id Id as entered in placeholder.
     * @param Tag $tag Container tag.
     */
    abstract public function getExpression($id, Tag $tag);
}