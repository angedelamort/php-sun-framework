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
     * @param array $options
     * @param callback $onRemove See Cache constructor
     * @throws Exception
     */
    // TODO: make some parameters or use an object.
    public static function init(array $options, $onRemove = null) {
        self::$cache = new Cache($options, $onRemove);
    }

    /**
     * @return Cache
     */
    public static function instance() {
        return self::$cache;
    }
}