<?php

namespace TotalCRM\DocxTemplator;

use TotalCRM\DocxTemplator\Core\CommentTransformer;
use TotalCRM\DocxTemplator\Document\DocumentInterface;
use TotalCRM\DocxTemplator\Processor\Lexer;
use TotalCRM\DocxTemplator\Processor\TagMapper;
use ReflectionException;
use XSLTProcessor;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;

/**
 * Class Templator
 * @package TotalCRM\DocxTemplator
 */
class Templator
{
    public bool $debug = false;
    public bool $trackDocument = false;
    private string $cachePath;
    private array $brackets;

    /**
     * Templator constructor.
     * @param $cachePath
     * @param array $brackets
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($cachePath, $brackets = array('[[', ']]'))
    {
        if (!is_dir($cachePath)) {
            throw new Exception\InvalidArgumentException('Cache path "' . $cachePath . '" unreachable.');
        }
        if (!is_writable($cachePath)) {
            throw new Exception\InvalidArgumentException('Cache path "' . $cachePath . '" not writable.');
        }
        if (count($brackets) !== 2 || array_values($brackets) !== $brackets) {
            throw new Exception\InvalidArgumentException('Brackets are in wrong format.');
        }

        $this->cachePath = $cachePath;
        $this->brackets = $brackets;
    }

    /**
     * @param DocumentInterface $document Document to render.
     * @param array $values Multidimensional array with values to replace placeholders.
     * @return Result
     * @throws Exception\InvalidArgumentException
     * @throws Exception\ParsingException
     * @throws Exception\ProcessorException
     * @throws ReflectionException
     */
    public function render(DocumentInterface $document, array $values): Result
    {
        $xslt = new XSLTProcessor();

        $template = $this->getTemplate($document);
        $xslt->importStylesheet($template);

        $content = $xslt->transformToDoc(
            $this->createValuesDocument($values)
        );

        Processor::undoEscapeXsl($content);

        return new Result($content, $document);
    }

    /**
     * @param DocumentInterface $document Document to render.
     * @return DOMDocument XSL stylesheet.
     * @throws Exception\ParsingException
     * @throws Exception\ProcessorException
     * @throws ReflectionException
     */
    private function getTemplate(DocumentInterface $document): DOMDocument
    {
        $overwrite = false;
        if ($this->trackDocument === true) {
            $overwrite = $this->compareHash($document);
        }

        $contentFile = $document->extract($this->cachePath, $this->debug || $overwrite);

        $template = new DOMDocument('1.0', 'UTF-8');
        $template->load($contentFile);

        if ($template->documentElement->nodeName !== 'xsl:stylesheet') {
            $this->createTemplate($template, $document);
            $this->storeComment($template, $document);

            $template->save($contentFile);
            $template->load($contentFile);
        }

        return $template;
    }

    /**
     * @param DOMDocument $template Main content file.
     * @param DocumentInterface $document Document to render.
     * @throws Exception\ParsingException
     * @throws Exception\ProcessorException
     * @throws ReflectionException
     */
    private function createTemplate(DOMDocument $template, DocumentInterface $document): void
    {
        Processor::escapeXsl($template);

        $document->cleanup($template);
        Processor::wrapIntoTemplate($template);

        $query = $document->getNodePath();
        $query .= sprintf(
            '[contains(text(), "%s") and contains(text(), "%s")]',
            $this->brackets[0],
            $this->brackets[1]
        );
        $nodeList = XMLHelper::queryTemplate($template, $query);
        $this->searchAndReplace($nodeList, $document);
    }

    /**
     * @param DOMNodeList $nodeList List of nodes having at least one placeholder.
     * @param DocumentInterface $document Document to render.
     * @throws Exception\ParsingException
     * @throws Exception\ProcessorException
     * @throws ReflectionException
     */
    private function searchAndReplace(DOMNodeList $nodeList, DocumentInterface $document): void
    {
        $lexer = new Lexer($this->brackets);
        $mapper = new TagMapper;

        /** @var $node DOMElement */
        foreach ($nodeList as $node) {
            $decodedValue = utf8_decode($node->nodeValue);

            $lexer->setInput($decodedValue);

            while ($tag = $mapper->parse($lexer)) {

                foreach ($tag->getFunctions() as $function) {
                    $expression = $document->getExpression($function['function'], $tag);
                    $expression->execute($function['arguments'], $node);
                }

                // insert simple value-of
                if ($tag->hasFunctions() === false) {
                    $absolutePath = '/' . Processor::VALUE_NODE . '/' . $tag->getXmlPath();
                    Processor::insertTemplateLogic($tag->getTextContent(), $absolutePath, $node);
                }
            }
        }
    }

    /**
     * @param array $values Multidimensional array.
     * @return DOMDocument
     */
    private function createValuesDocument(array $values): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');

        $tokensNode = $document->createElement(Processor::VALUE_NODE);
        $document->appendChild($tokensNode);

        XMLHelper::xmlEncode($values, $tokensNode, $document);

        return $document;
    }

    /**
     * @param DocumentInterface $document Document to render.
     * @return bool Document was updated?
     */
    private function compareHash(DocumentInterface $document): bool
    {
        $overwrite = false;

        $contentPath = $this->cachePath . $document->getDocumentName() . '/' . $document->getContentPath();
        if (file_exists($contentPath) === true) {

            $template = new DOMDocument('1.0', 'UTF-8');
            $template->load($contentPath);

            $query = new DOMXPath($template);
            $commentList = $query->query('/xsl:stylesheet/comment()');

            if ($commentList->length === 1) {
                $commentNode = $commentList->item(0);

                $commentContent = $commentNode->nodeValue ?? '';
                $commentContent = trim($commentContent);

                $transformer = new CommentTransformer();
                $contentMeta = $transformer->reverseTransformer($commentContent);

                if ($document->getDocumentHash() !== $contentMeta['document_hash']) {
                    $overwrite = true;
                }
            }
        }

        return $overwrite;
    }

    /**
     * @param DOMDocument $template XSL stylesheet.
     * @param DocumentInterface $document Document to render.
     */
    private function storeComment(DOMDocument $template, DocumentInterface $document): void
    {
        $meta = array(
            'generation_date' => date('Y-m-d H:i:s'),
            'document_hash' => $document->getDocumentHash()
        );

        $transformer = new CommentTransformer();
        $commentContent = $transformer->transform($meta);

        $commentNode = $template->createComment($commentContent);
        $template->documentElement->appendChild($commentNode);
    }
}