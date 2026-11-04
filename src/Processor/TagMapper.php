<?php

namespace TotalCRM\DocxTemplator\Processor;

use TotalCRM\DocxTemplator\Processor\Lexer;
use TotalCRM\DocxTemplator\Exception\ParsingException;
use TotalCRM\DocxTemplator\Exception\ProcessorException;
use ReflectionException;

class TagMapper
{
    public function parse(Lexer $lexer): ?Tag
    {
        while ($lexer->moveNext()) {
            if ($lexer->isNextToken(Lexer::T_OPEN_BRACKET) === true) {
                $tagData = $this->parseTag($lexer);
                return $this->mapObject($tagData);
            }
        }

        return null;
    }

    private function parseTag(Lexer $lexer): array
    {
        $tagData = array(
            'summary' => array(
                'textContent' => '',
                'position' => $lexer->lookahead->position, // currently on Lexer::T_OPEN_BRACKET
                'length' => 0
            ),
            'path' => array(),
            'functions' => array()
        );

        $tagData['path'] = $this->parsePath($lexer);

        while ($lexer->isNextToken(Lexer::T_COLON)) {
            $tagData['functions'][] = $this->parseFunction($lexer);
        }

        $expected = Lexer::T_CLOSE_BRACKET;
        if ($lexer->isNextToken($expected) === false) {
            throw new ParsingException(
                'Unexpected token' .
                ', expected ' . $lexer->getLiteral($expected) .
                ', got ' . $lexer->getLiteral($lexer->lookahead->type)
            );
        }

        $endAt = $lexer->lookahead->position + mb_strlen($lexer->lookahead->value);
        $tagData['summary']['length'] = $endAt - $tagData['summary']['position'];

        $tagData['summary']['textContent'] = $lexer->getInputBetweenPosition(
            $tagData['summary']['position'],
            $tagData['summary']['length']
        );

        return $tagData;
    }

    private function parsePath(Lexer $lexer, $delimiter = Lexer::T_COLON, $return = Lexer::T_CLOSE_BRACKET): array
    {
        $path = array();
        $expected = Lexer::T_STRING;

        while ($lexer->moveNext()) {
            $token = $lexer->lookahead;

            if ($token->type === $delimiter || $token->type === $return) {
                return $path;
            }

            if ($token->type !== $expected) {
                throw new ProcessorException(
                    'Unexpected token' .
                    ', expected ' . $lexer->getLiteral($expected) .
                    ', got ' . $lexer->getLiteral($token->type)
                );
            }

            switch ($token->type) {
                case Lexer::T_STRING:
                    $expected = Lexer::T_DOT;
                    $path[] = $token->value;
                    break;

                case Lexer::T_DOT:
                    $expected = Lexer::T_STRING;
                    break;
            }
        }

        return $path;
    }

    private function parseFunction(Lexer $lexer, $delimiter = Lexer::T_COLON, $return = Lexer::T_CLOSE_BRACKET)
    {
        $function = null;
        $arguments = array();

        $expected = Lexer::T_STRING;
        $optional = null;

        while ($lexer->moveNext()) {
            $token = $lexer->lookahead;

            if ($token->type === $delimiter || $token->type === $return) {
                return array('function' => $function, 'arguments' => $arguments);
            }

            if ($token->type !== $expected && $token->type !== $optional) {
                throw new ProcessorException(
                    'Unexpected token' .
                    ', expected ' . $lexer->getLiteral($expected) .
                    ', got ' . $lexer->getLiteral($token->type)
                );
            }

            $optional = null;

            switch ($token->type) {
                case Lexer::T_STRING:
                    if ($function === null) {
                        $function = $token->value;
                        $expected = Lexer::T_OPEN_PARENTHESIS;
                        $optional = null;
                        break;
                    }

                    $arguments[] = $token->value;
                    $expected = Lexer::T_CLOSE_PARENTHESIS;
                    $optional = Lexer::T_COMMA;
                    break;

                case Lexer::T_COMMA:
                    $expected = Lexer::T_STRING;
                    break;

                case Lexer::T_OPEN_PARENTHESIS:
                    $expected = Lexer::T_CLOSE_PARENTHESIS;
                    $optional = Lexer::T_STRING;
                    break;

                case Lexer::T_CLOSE_PARENTHESIS:
                    $expected = $return;
                    $optional = $delimiter;
                    break;
            }
        }

        return array('function' => $function, 'arguments' => $arguments);
    }

    private function mapObject(array $tagData): Tag
    {
        return new Tag($tagData['summary'], $tagData['path'], $tagData['functions']);
    }
}
