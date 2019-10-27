<?php

namespace sunframework\cache;


use Exception;

/**
 * Class CacheFactory
 * @package sunframework\cache
 *
 * Singleton class to help use the same Cache instance everywhere.
 */
final class CacheFactory {
    /** @var Cache */
    private static $cache = null;

    private function __construct() {}

    /**
     * @param Cache $cache
     */
    public static function init(Cache $cache) {
        self::$cache = $cache;
    }

    /**
     * @return Cache
     */
    public static function instance() {
        return self::$cache;
    }
}