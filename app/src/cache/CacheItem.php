<?php

namespace sunframework\cache;

use DateInterval;
use DateTime;
use Exception;

final class CacheItem {
    /**
     * CacheItem constructor.
     * @param string $key
     * @param $value
     * @param DateTime|null $absoluteExpiration
     * @param DateInterval|null $slidingExpiration
     * @param CacheDependency|null $dependency
     * @throws Exception
     */
    public function __construct(string $key, $value, ?DateTime $absoluteExpiration, ?DateInterval $slidingExpiration, ?CacheDependency $dependency) {
        $this->key = $key;
        $this->value = $value;
        $this->absoluteExpiration = $absoluteExpiration;
        $this->slidingExpiration =  $slidingExpiration;
        $this->slidingExpirationEnd =  $slidingExpiration ? (new DateTime())->add($slidingExpiration) : null;
        $this->dependency = $dependency;
    }

    /** @var string */
    public $key;
    /** @var mixed|null */
    public $value;
    /** @var DateTime|null */
    public $absoluteExpiration;
    /** @var DateInterval|null */
    public $slidingExpiration;
    /** @var  DateTime|null */
    public $slidingExpirationEnd;
    /** @var CacheDependency|null */
    public $dependency;
}