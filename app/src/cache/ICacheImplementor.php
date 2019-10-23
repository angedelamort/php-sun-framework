<?php

namespace sunframework\cache;

use DateInterval;
use DateTime;

interface ICacheImplementor {
    public function get(string $key);
    public function add(string $key, $value, CacheDependency $dependency, DateTime $absoluteExpiration, DateInterval $slidingExpiration);
    public function insert(string $key, $value, CacheDependency $dependency, DateTime $absoluteExpiration, DateInterval $slidingExpiration);
    public function remove(string $key);
}