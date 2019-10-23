<?php

namespace sunframework\cache;


use DateInterval;
use DateTime;
use Exception;

/**
 * Class Cache
 * @package sunframework\cache
 * Implements a cache for a Web application.
 *
 * Remark: Support only file-based for now.
 */
final class Cache {

    /**
     * @var $cacheImp ICacheImplementor
     */
    private $cacheImp;

    /**
     * Cache constructor.
     * @param array $options
     *  options: {
     *     db: {files}
     *
     *     files-path: string
     *  }
     * @param callback|null $onRemove($key, $reason)
     * @throws Exception
     */
    public function __construct(array $options, $onRemove = null) {
        if (isset($options['db'])) {
            switch ($options['db']) {
                case 'files':
                    $this->cacheImp = new FileCacheImplementor($options['files-path'], $onRemove);
                    break;
                default:
                    throw new Exception("Not Supported.");
            }
        }
    }

    /**
     * Retrieves the specified item from the Cache object.
     * @param string $key The identifier for the cache item to retrieve.
     * @return object The cache item, or null if the key is not found.
     * @throws Exception
     */
    public function get(string $key) {
        return $this->cacheImp->get($key);
    }

    /**
     * Adds the specified item to the Cache object.
     * Note: if the object is already set, will fail silently.
     * @param string $key The cache key used to reference the item.
     * @param $value mixed The item to be added to the cache.
     * @param CacheDependency|null $dependency The file. When any dependency changes, the object becomes invalid and is removed from the cache.
     * @param DateTime|null $absoluteExpiration The time at which the added object expires and is removed from the cache. If you are using sliding expiration, the absoluteExpiration parameter must be null
     * @param DateInterval|null $slidingExpiration The interval between the time the added object was last accessed and the time at which that object expires.
     * @return object The item that was added, null otherwise.
     */
    public function add(string $key, $value, CacheDependency $dependency = null,
                        DateTime $absoluteExpiration = null, DateInterval $slidingExpiration = null) {
        return $this->cacheImp->add($key, $value, $dependency, $absoluteExpiration, $slidingExpiration);
    }

    /**
     * Adds the specified item to the Cache object. Use this method to overwrite an existing cache item.
     * Note: if the object is already set, will override.
     * @param string $key The cache key used to reference the item.
     * @param $value mixed The item to be added to the cache.
     * @param CacheDependency|null $dependency The file. When any dependency changes, the object becomes invalid and is removed from the cache.
     * @param DateTime|null $absoluteExpiration The time at which the added object expires and is removed from the cache. If you are using sliding expiration, the absoluteExpiration parameter must be null
     * @param DateInterval|null $slidingExpiration The interval between the time the added object was last accessed and the time at which that object expires.
     * @return object The item that was added/updated.
     */
    public function insert(string $key, $value, CacheDependency $dependency = null,
                        DateTime $absoluteExpiration = null, DateInterval $slidingExpiration = null) {
        return $this->cacheImp->insert($key, $value, $dependency, $absoluteExpiration, $slidingExpiration);
    }

    /**
     * Removes the specified item from the application's Cache object.
     * @param string $key The cache key used to reference the item.
     */
    public function remove(string $key) {
        return $this->cacheImp->remove($key);
    }
}