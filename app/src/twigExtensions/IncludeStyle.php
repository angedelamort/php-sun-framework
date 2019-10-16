<?php
namespace sunframework\twigExtensions;

class IncludeStyle extends \Twig\Extension\AbstractExtension
{
    public function getTokenParsers()
    {
        return [new IncludeStyle_TokenParser()];
    }
}