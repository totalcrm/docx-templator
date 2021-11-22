<?php

namespace TotalCRM\DocxTemplator\Extension;

use TotalCRM\DocxTemplator\Processor\Tag;
use DOMElement;

/**
 * Interface ExtensionInterface
 * @package TotalCRM\DocxTemplator\Extension
 */
interface ExtensionInterface
{
    /**
     * ExtensionInterface constructor.
     * @param Tag $tag
     */
    public function __construct(Tag $tag);

    /**
     * @param array $arguments
     * @param DOMElement $node
     * @return void
     */
    public function execute(array $arguments, DOMElement $node);
} 