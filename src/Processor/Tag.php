<?php

namespace TotalCRM\DocxTemplator\Processor;

/**
 * Class Tag
 * @package TotalCRM\DocxTemplator\Processor
 */
class Tag
{
    private array $summary;
    private array $path;
    private array $functions;

    /**
     * Tag constructor.
     * @param array $summary
     * @param array $path
     * @param array $functions
     */
    public function __construct(array $summary, array $path, array $functions)
    {
        $this->summary = $summary;
        $this->path = $path;
        $this->functions = $functions;

    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->summary['position'];
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->summary['length'];
    }

    /**
     * @return string
     */
    public function getXmlPath(): string
    {
        return implode('/', $this->path);
    }

    /**
     * @return string
     */
    public function getRelativePath(): string
    {
        return end($this->path);
    }

    /**
     * @return bool
     */
    public function hasFunctions(): bool
    {
        return (count($this->functions) !== 0);
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * @return string
     */
    public function getTextContent(): string
    {
        return $this->summary['textContent'];
    }
} 