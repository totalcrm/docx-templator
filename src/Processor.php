<?php

namespace TotalCRM\DocxTemplator;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;

/**
 * Class Processor
 * @package TotalCRM\DocxTemplator
 */
class Processor
{
    public const XSL_NS = 'http://www.w3.org/1999/XSL/Transform';
    public const VALUE_NODE = 'values';

    /**
     * @param DOMDocument $document
     */
    public static function wrapIntoTemplate(DOMDocument $document): void
    {
        $stylesheet = $document->createElementNS(self::XSL_NS, 'xsl:stylesheet');
        $stylesheet->setAttribute('version', '1.0');

        $output = $document->createElementNS(self::XSL_NS, 'xsl:output');
        $output->setAttribute('method', 'xml');
        $output->setAttribute('encoding', 'UTF-8');
        $stylesheet->appendChild($output);

        $template = $document->createElementNS(self::XSL_NS, 'xsl:template');
        $template->setAttribute('match', '/' . self::VALUE_NODE);
        $template->appendChild($document->documentElement);
        $stylesheet->appendChild($template);

        $document->appendChild($stylesheet);
    }

    /**
     * @param string $search Placeholder tag with brackets.
     * @param string $path XPath to value in the encoded XML document.
     * @param DOMElement $node Node with placeholder.
     * @return bool
     */
    public static function insertTemplateLogic($search, $path, DOMElement $node): bool
    {
        $template = $node->ownerDocument;

        $node->setAttribute('xml:space', 'preserve');

        /** @var $textNode DOMText */
        foreach ($node->childNodes as $textNode) {
            $nodeValue = $textNode->nodeValue;
            $nodeValueParts = explode($search, $nodeValue, 2);

            if (count($nodeValueParts) === 2) {
                $textNode->nodeValue = '';
                $before = $template->createTextNode($nodeValueParts[0]);
                $node->insertBefore($before, $textNode);

                $placeholder = $template->createElementNS(self::XSL_NS, 'xsl:value-of');
                $placeholder->setAttribute('select', $path);
                $node->insertBefore($placeholder, $textNode);

                $after = $template->createTextNode($nodeValueParts[1]);
                $node->insertBefore($after, $textNode);

                $node->removeChild($textNode);

                return true;
            }
        }

        return false;
    }

    /**
     * Word XML can contain curly braces in attributes, which conflicts with XSL logic.
     * Escape them before template created.
     *
     * @param DOMDocument $document
     */
    public static function escapeXsl(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $nodeList = $xpath->query('//*[contains(@uri, "{") and contains(@uri ,"}")]');
        /** @var $node DOMNode */
        foreach ($nodeList as $node) {
            /** @var $attr DOMAttr */
            foreach ($node->attributes as $attr) {
                $attr->nodeValue = str_replace(array('{', '}'), array('{{', '}}'), $attr->nodeValue);
            }
        }
    }

    /**
     * Word XML can contain curly braces in attributes, which conflicts with XSL logic.
     * Undo escape them after template conversion.
     *
     * @param DOMDocument $document
     */
    public static function undoEscapeXsl(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $nodeList = $xpath->query('//*[contains(@uri, "{{") and contains(@uri ,"}}")]');
        /** @var $node DOMNode */
        foreach ($nodeList as $node) {
            /** @var $attr DOMAttr */
            foreach ($node->attributes as $attr) {
                $attr->nodeValue = str_replace(array('{{', '}}'), array('{', '}'), $attr->nodeValue);
            }
        }
    }
}