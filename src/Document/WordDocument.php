<?php

namespace TotalCRM\DocxTemplator\Document;

use DOMDocument;
use TotalCRM\DocxTemplator\Document\WordDocument\Cleanup;
use TotalCRM\DocxTemplator\Exception\InvalidArgumentException;
use TotalCRM\DocxTemplator\Extension\Extension;
use TotalCRM\DocxTemplator\Processor\Tag;
use TotalCRM\DocxTemplator\Document\WordDocument\Extension\Cell;
use TotalCRM\DocxTemplator\Document\WordDocument\Extension\ListItem;

/**
 * Class WordDocument
 * @package TotalCRM\DocxTemplator\Document
 */
class WordDocument extends Document
{
    private $structure = array('w:p', 'w:r', 'w:rPr', 'w:t');

    /**
     * Path to main content file inside document ZIP archive.
     */
    public function getContentPath()
    {
        return 'word/document.xml';
    }

    /**
     * Get node name by XPATH_* constant type.
     *
     * @param int $type XPATH_* constant.
     * @param bool $global Append global xpath //.
     * @return string
     * @throws InvalidArgumentException
     */
    public function getNodeName($type, $global = false)
    {
        if (isset($this->structure[$type]) === false) {
            throw new InvalidArgumentException('Element with this index not defined in structure');
        }

        $return = array();
        if ($global === true) {
            $return[] = '//';
        }
        $return[] = $this->structure[$type];

        return implode($return);
    }

    /**
     * XPath to text node.
     */
    public function getNodePath()
    {
        return '//w:p/w:r/w:t';
    }

    /**
     * Cleanup Word Document from WYSIWYG mess.
     *
     * @param DOMDocument $template
     * @throws InvalidArgumentException
     */
    public function cleanup(DOMDocument $template)
    {
        // fix node breaks
        $cleaner = new Cleanup(
            $template,
            $this->getNodeName(Document::XPATH_PARAGRAPH, true),
            $this->getNodeName(Document::XPATH_RUN),
            $this->getNodeName(Document::XPATH_RUN_PROPERTY),
            $this->getNodeName(Document::XPATH_TEXT)
        );

        $cleaner->hardcoreCleanup();
        $cleaner->cleanup();
    }

    /**
     * Get instance of associated placeholder function.
     *
     * @param string $id Id as entered in placeholder.
     * @param Tag $tag Container tag.
     * @return Extension
     * @throws InvalidArgumentException
     */
    public function getExpression($id, Tag $tag)
    {
        $available = array(
            'cell' => Cell::class,
            'listitem' => ListItem::class,
        );

        if (isset($available[$id]) === false) {
            throw new InvalidArgumentException('Class by id "' . $id . '" not found.');
        }

        $className = $available[$id];
        return new $className($tag);
    }
}
