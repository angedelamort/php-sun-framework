<?php
namespace sunframework\system;

class StringUtil {
    /**
     * @param string $haystack
     * @param string $needle
     * @return bool true if the needle starts with the $haystack
     */
    public static function startsWith(string $haystack, string $needle) {
        return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
    }

    /**
     * @param $haystack
     * @param $needle
     * @return bool true if the needle ends with the $haystack
     */
    public static function endsWith($haystack, $needle) {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }
}