<?php

namespace TotalCRM\DocxTemplator\Document;

use DOMDocument;
use TotalCRM\DocxTemplator\Exception\InvalidArgumentException;
use TotalCRM\DocxTemplator\Extension\ExtensionInterface;
use TotalCRM\DocxTemplator\Processor\Tag;

/**
 * Interface DocumentInterface
 * @package TotalCRM\DocxTemplator\Document
 */
interface DocumentInterface
{
    /**
     * DocumentInterface constructor.
     * @param string $documentPath
     * @throws InvalidArgumentException
     */
    public function __construct($documentPath);

    public function extract($to, $overwrite);

    public function getDocumentHash();

    public function getDocumentName();

    public function getDocumentPath();

    public function cleanup(DOMDocument $template);

    public function getContentPath();

    public function getNodePath();

    public function getNodeName($type, $global = false);

    public function getExpression($id, Tag $tag);
} 