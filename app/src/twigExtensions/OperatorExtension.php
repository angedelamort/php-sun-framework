<?php

namespace sunframework\twigExtensions;


use Twig\Extension\AbstractExtension;

class OperatorExtension extends AbstractExtension
{
    public function getOperators()
    {
        return [
            // Unary Operators
            [],

            // Binary Operators
            [
                '<<' => ['precedence' => 60, 'class' => LeftBitShift::class],
                '>>' => ['precedence' => 60, 'class' => RightBitShift::class],
                'b-left-shift' => ['precedence' => 60, 'class' => LeftBitShift::class],
                'b-right-shift' => ['precedence' => 60, 'class' => RightBitShift::class]
            ]
        ];
    }
}