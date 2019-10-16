<?php
namespace sunframework\twigExtensions;

use Twig\Compiler;
use Twig\Node\Expression\Binary\AbstractBinary;

class LeftBitShift extends AbstractBinary {
    public function operator(Compiler $compiler) {
        return $compiler->raw('<<');
    }
}