<?php

namespace TotalCRM\DocxTemplator\Extension;

use TotalCRM\DocxTemplator\Processor\Tag;
use DOMElement;

/**
 * Class Extension
 * @package TotalCRM\DocxTemplator\Extension
 */
abstract class Extension implements ExtensionInterface
{
    protected Tag $tag;

    /**
     * Extension constructor.
     * @param Tag $tag
     */
    public function __construct(Tag $tag)
    {
        $this->tag = $tag;
    }

    /**
     * @param array $arguments
     * @param DOMElement $node
     */
    public function execute(array $arguments, DOMElement $node): void
    {
        $arguments = $this->prepareArguments($arguments);
        $this->insertTemplateLogic($arguments, $node);
    }

    /**
     * Prepare / validate / merge with defaults / modify given arguments.
     * @param array $arguments
     * @return array
     */
    abstract protected function prepareArguments(array $arguments);

    /**
     * All template modification magic is here.
     * @param array $arguments
     * @param DOMElement $node
     * @return void
     */
    abstract protected function insertTemplateLogic(array $arguments, DOMElement $node);
}