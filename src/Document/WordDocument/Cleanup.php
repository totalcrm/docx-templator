<?php

namespace TotalCRM\DocxTemplator\Document\WordDocument;

use TotalCRM\DocxTemplator\XMLHelper;
use DOMNodeList;
use DOMDocument;
use DOMXPath;
use DOMNode;

/**
 * Class Cleanup
 * @package TotalCRM\DocxTemplator\Document\WordDocument
 */
class Cleanup extends XMLHelper
{
    private DOMDocument $document;
    private DOMXPath $xpath;

    private $paragraphQuery;
    private $runQuery;
    private $runPropertyQuery;
    private $textQuery;

    /**
     * Cleanup constructor.
     * @param DOMDocument $document
     * @param $paragraphQuery
     * @param $runQuery
     * @param $propertyQuery
     * @param $textQuery
     */
    public function __construct(DOMDocument $document, $paragraphQuery, $runQuery, $propertyQuery, $textQuery)
    {
        $this->document = $document;
        $this->xpath = new DOMXPath($document);

        $this->paragraphQuery = $paragraphQuery;
        $this->runQuery = $runQuery;
        $this->runPropertyQuery = $propertyQuery;
        $this->textQuery = $textQuery;
    }

    public function cleanup(): void
    {
        $paragraphNodeList = $this->getParagraphNodeList();

        /** @var $paragraphNode DOMNode */
        foreach ($paragraphNodeList as $paragraphNode) {
            $clonedParagraphNode = $paragraphNode->cloneNode(true);
            $runNodeList = $this->getRunNodeList($clonedParagraphNode);

            $runIndex = 0;
            $currentRunNode = $runNodeList->item($runIndex);

            ++$runIndex;
            $nextRunNode = $runNodeList->item($runIndex);

            while ($currentRunNode) {
                if ($nextRunNode !== null) {
                    $isEqual = $this->deepEqual(
                        $this->getPropertyNode($currentRunNode),
                        $this->getPropertyNode($nextRunNode)
                    );

                    if ($this->getValueNode($currentRunNode) === null || $this->getValueNode($nextRunNode) === null) {
                        $isEqual = false;
                    }

                    if ($isEqual === true) {
                        $nextValueNode = $this->getValueNode($nextRunNode);
                        /** @var DOMNode $currentValueNode */
                        $currentValueNode = $this->getValueNode($currentRunNode);

                        if ($nextValueNode !== null && $currentValueNode !== null) {
                            $appendTextNode = $this->document->createTextNode($nextValueNode->textContent);
                            $currentValueNode->appendChild($appendTextNode);

                            if ($currentValueNode->hasAttribute('xml:space') === false &&
                                $currentValueNode->textContent !== trim($currentValueNode->textContent)) {
                                $currentValueNode->setAttribute('xml:space', 'preserve');
                            }
                        }
                        $clonedParagraphNode->removeChild($nextRunNode);
                    } else {
                        $currentRunNode = $nextRunNode;
                    }

                    ++$runIndex;
                    $nextRunNode = $runNodeList->item($runIndex);

                } else {
                    $currentRunNode = $nextRunNode;
                }
            }
            $paragraphNode->parentNode->replaceChild($clonedParagraphNode, $paragraphNode);
        }

        $this->document->normalizeDocument();
    }

    /**
     * @return DOMNodeList|false
     */
    private function getParagraphNodeList()
    {
        return $this->xpath->query($this->paragraphQuery);
    }

    /**
     * @param DOMNode $paragraphNode
     * @return DOMNodeList|false
     */
    private function getRunNodeList(DOMNode $paragraphNode)
    {
        return $this->xpath->query($this->runQuery, $paragraphNode);
    }

    /**
     * @param DOMNode $runNode
     * @return DOMNode|null
     */
    private function getPropertyNode(DOMNode $runNode): ?DOMNode
    {
        $nodeList = $this->xpath->query($this->runPropertyQuery, $runNode);
        return $nodeList->item(0);
    }

    /**
     * @param DOMNode $runNode
     * @return DOMNode|null
     */
    private function getValueNode(DOMNode $runNode): ?DOMNode
    {
        $nodeList = $this->xpath->query($this->textQuery, $runNode);
        return $nodeList->item(0);
    }

    public function hardcoreCleanup(): void
    {
        $nodeList = $this->xpath->query('//w:lang');
        /** @var $langNode DOMNode */
        foreach ($nodeList as $langNode) {
            $langNode->parentNode->removeChild($langNode);
        }

        $nodeList = $this->xpath->query('//' . $this->runPropertyQuery . '[not(node())]');
        /** @var $langNode DOMNode */
        foreach ($nodeList as $node) {
            $node->parentNode->removeChild($node);
        }
    }
}
