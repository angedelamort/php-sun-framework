<?php
namespace sunframework\twigExtensions;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class IncludeStyle_TokenParser extends AbstractTokenParser
{
    private $settings = [];

    public function __construct($settings = []) {
        $this->settings = $settings;
    }

    public function parse(Token $token)
    {
        $parser = $this->parser;
        $stream = $parser->getStream();

        //$name = $stream->expect(\Twig\Token::NAME_TYPE)->getValue();
        //$stream->expect(\Twig\Token::OPERATOR_TYPE, '=');
        //$value = $parser->getExpressionParser()->parseExpression();
        $value = $parser->getExpressionParser()->parseExpression();
        $stream->expect(Token::BLOCK_END_TYPE);

        return new IncludeStyle_Set_Node($value, $this->settings, $token->getLine(), $this->getTag());
    }

    public function getTag()
    {
        return 'style';
    }
}