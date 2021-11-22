<?php

namespace TotalCRM\DocxTemplator\Processor;

use Doctrine\Common\Lexer\AbstractLexer;
use ReflectionClass;
use ReflectionException;

/**
 * Class Lexer
 * @package TotalCRM\DocxTemplator\Processor
 */
class Lexer extends AbstractLexer
{
    public const T_NONE = 1;
    public const T_INTEGER = 2;
    public const T_STRING = 3;
    public const T_INPUT_PARAMETER = 4;
    public const T_FLOAT = 5;
    public const T_CLOSE_PARENTHESIS = 6;
    public const T_OPEN_PARENTHESIS = 7;
    public const T_COMMA = 8;
    public const T_DIVIDE = 9;
    public const T_DOT = 10;
    public const T_EQUALS = 11;
    public const T_GREATER_THAN = 12;
    public const T_LOWER_THAN = 13;
    public const T_MINUS = 14;
    public const T_MULTIPLY = 15;
    public const T_NEGATE = 16;
    public const T_PLUS = 17;
    public const T_OPEN_CURLY_BRACE = 18;
    public const T_CLOSE_CURLY_BRACE = 19;
    public const T_COLON = 20;
    public const T_OPEN_BRACKET = 100;
    public const T_CLOSE_BRACKET = 101;
    private array $brackets;

    /**
     * Lexer constructor.
     * @param array $brackets
     */
    public function __construct(array $brackets)
    {
        $this->brackets = $brackets;
    }

    /**
     * @return array
     */
    protected function getCatchablePatterns(): array
    {
        return array(
            '[a-z_\\\][a-z0-9_\\\]*[a-z0-9_]{1}',
            '(?:[0-9]+(?:[\.][0-9]+)*)(?:e[+-]?[0-9]+)?',
            "'(?:[^']|''|')*'",
            '\?[0-9]*|[a-z_][a-z0-9_]*',
            '(?:' . preg_quote($this->brackets[0] ?? '') . ')',
            '(?:' . preg_quote($this->brackets[1] ?? '') . ')'
        );
    }

    /**
     * @return array
     */
    protected function getNonCatchablePatterns(): array
    {
        return array('\s+', '(.)');
    }

    /**
     * @param string $value
     * @return int|null
     */
    protected function getType(&$value): ?int
    {
        switch (true) {
            case ($value === $this->brackets[0]):
                return self::T_OPEN_BRACKET;
            case ($value === $this->brackets[1]):
                return self::T_CLOSE_BRACKET;
            case ($value === '.'):
                return self::T_DOT;
            case ($value === ','):
                return self::T_COMMA;
            case ($value === '('):
                return self::T_OPEN_PARENTHESIS;
            case ($value === ')'):
                return self::T_CLOSE_PARENTHESIS;
            case ($value === '='):
                return self::T_EQUALS;
            case ($value === '>'):
                return self::T_GREATER_THAN;
            case ($value === '<'):
                return self::T_LOWER_THAN;
            case ($value === '+'):
                return self::T_PLUS;
            case ($value === '-'):
                return self::T_MINUS;
            case ($value === '*'):
                return self::T_MULTIPLY;
            case ($value === '/'):
                return self::T_DIVIDE;
            case ($value === '!'):
                return self::T_NEGATE;
            case ($value === '{'):
                return self::T_OPEN_CURLY_BRACE;
            case ($value === '}'):
                return self::T_CLOSE_CURLY_BRACE;
            case ($value === ':'):
                return self::T_COLON;
            case (is_string($value)):
                return self::T_STRING;
            default:
                return self::T_NONE;
        }
    }

    /**
     * @param int $position
     * @param int $length
     * @return string
     * @throws ReflectionException
     */
    public function getInputBetweenPosition($position, $length): string
    {
        $reflectionClass = new ReflectionClass('Doctrine\Common\Lexer\AbstractLexer');
        $reflectionProperty = $reflectionClass->getProperty('input');
        $reflectionProperty->setAccessible(true);
        $input = $reflectionProperty->getValue($this);

        return mb_substr($input, $position, $length);
    }
}