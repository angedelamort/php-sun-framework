<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2018 BuzzingPixel, LLC
 * @license Apache-2.0
 */

namespace sunframework\twigExtensions;

use Twig\Extension\AbstractExtension;

class SwitchTwigExtension extends AbstractExtension {
    public function getTokenParsers(): array {
        return [
            new SwitchTokenParser()
        ];
    }
}
