<?php

namespace TotalCRM\DocxTemplator\Document\WordDocument\Extension;

use TotalCRM\DocxTemplator\Exception\ExtensionException;
use TotalCRM\DocxTemplator\Exception\ParsingException;
use TotalCRM\DocxTemplator\Extension\Extension;
use TotalCRM\DocxTemplator\Processor;
use TotalCRM\DocxTemplator\XMLHelper;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Class ListItem
 * @package TotalCRM\DocxTemplator\Document\WordDocument\Extension
 */
class ListItem extends Extension
{
    /**
     * @param array $arguments
     * @return array
     * @throws ExtensionException
     */
    protected function prepareArguments(array $arguments): array
    {
        if (count($arguments) !== 0) {
            throw new ExtensionException('Wrong arguments number, 0 needed, got ' . count($arguments));
        }

        return $arguments;
    }

    /**
     * @param array $arguments
     * @param DOMElement $node
     * @throws ExtensionException
     * @throws ParsingException
     */
    protected function insertTemplateLogic(array $arguments, DOMElement $node): void
    {
        $template = $node->ownerDocument;

        $listName = $this->tag->getRelativePath();

        if ($this->isListItemTemplateExist($listName, $template) === false) {

            $rowTemplate = $template->createElementNS(Processor::XSL_NS, 'xsl:template');
            $rowTemplate->setAttribute('name', $listName);

            $rowNode = XMLHelper::parentUntil('w:p', $node);

            $foreachNode = $template->createElementNS(Processor::XSL_NS, 'xsl:for-each');
            $foreachNode->setAttribute('select', '/' . Processor::VALUE_NODE . '/' . $listName . '/item');
            $callTemplateNode = $template->createElementNS(Processor::XSL_NS, 'xsl:call-template');
            $callTemplateNode->setAttribute('name', $listName);
            $foreachNode->appendChild($callTemplateNode);

            $rowNode->parentNode->insertBefore($foreachNode, $rowNode);

            $rowTemplate->appendChild($rowNode);
            $template->documentElement->appendChild($rowTemplate);

        }

        Processor::insertTemplateLogic($this->tag->getTextContent(), '.', $node);
    }

    /**
     * @param $rowName
     * @param DOMDocument $template
     * @return bool
     * @throws ExtensionException
     */
    private function isListItemTemplateExist($rowName, DOMDocument $template): bool
    {
        $xpath = new DOMXPath($template);
        $nodeList = $xpath->query('/xsl:stylesheet/xsl:template[@name="' . $rowName . '"]');

        if ($nodeList->length > 1) {
            throw new ExtensionException('Unexpected template count.');
        }

        return ($nodeList->length === 1);
    }
} 