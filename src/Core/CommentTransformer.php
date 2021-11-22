<?php

namespace TotalCRM\DocxTemplator\Core;

/**
 * Class CommentTransformer
 * @package TotalCRM\DocxTemplator\Core
 */
class CommentTransformer
{
    /**
     * @param array $comment
     * @return string
     */
    public function transform(array $comment): string
    {
        return json_encode($comment, JSON_THROW_ON_ERROR);
    }

    /**
     * @param $comment
     * @return mixed
     */
    public function reverseTransformer($comment)
    {
        return json_decode($comment, true, 512, JSON_THROW_ON_ERROR);
    }
}