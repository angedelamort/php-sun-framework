<?php

namespace sunframework\i18n;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;


/**
 * Class I18NTwigExtension
 * @package sunframework\i18n
 *
 * How to Use:
 *  {{ i18n('key') }}
 *  {{ i18n('key', replace1='value', replace2='value, ...) }}
 *
 * Can also be used as filter.
 *  {{ myArrayOfKeys | i18n | join(', ') }}
 */
class I18NTwigExtension extends AbstractExtension {
    
    public function getFunctions() {
        return [new TwigFunction('i18n', [$this, 'i18n'], ['is_variadic' => true])];
    }

    public function getFilters() {
        return [new TwigFilter('i18n', [$this, 'i18n'], ['is_variadic' => true])];
    }

    public function i18n($key, array $args = []) {
        if (is_array($key)) {
            $newArray = [];
            foreach ($key as $k => $v) {
                $newArray[$k] = I18n::text($v, $args);
            }
            return $newArray;
        }

        return I18n::text($key, $args);
    }
}