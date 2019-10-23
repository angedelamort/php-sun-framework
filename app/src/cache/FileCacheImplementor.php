<?php

namespace sunframework\cache;

use DateInterval;
use DateTime;
use Exception;

class FileCacheImplementor implements ICacheImplementor {

    private $cachePath;
    private $onRemove;

    /**
     * FileCacheImplementor constructor.
     * @param string $cachePath
     * @param $onRemove callback
     * @throws Exception
     */
    public function __construct(string $cachePath, $onRemove = null) {
        if (!file_exists($cachePath)) {
            if (!mkdir($cachePath)) {
                throw new Exception("Could not create the path '$cachePath'. Check your permission or the given path is valid.");
            }
        }

        $this->cachePath = $cachePath;
        $this->onRemove = $onRemove;
    }

    /**
     * @param string $key
     * @return false|object
     * @throws Exception
     */
    public function get(string $key) {
        $filename = $this->getFilename($key);
        $result = file_get_contents($filename);
        if ($result !== FALSE) {
            $item = json_decode($result);
            if ($item->key !== $key) {
                throw new Exception("Key Collision between '$key' and '$item[key]'");
            }

            $now = new DateTime();
            if ($item->absoluteExpiration && $now >= new DateTime($item->absoluteExpiration)) {
                $this->delete($key, $filename, 'expired');
                return FALSE;
            }  else if ($item->slidingExpiration) {
                if ($now >= new DateTime($item->slidingExpirationEnd)) {
                    $this->delete($key, $filename, 'expired');
                    return FALSE;
                } else {
                    $item->slidingExpirationEnd = $now->add(new DateInterval($item->slidingExpiration));
                    file_put_contents($filename, $item);
                }
            }

            if ($item->cacheDependency) {
                $dep = CacheDependency::deserialize($item->cacheDependency);
                if ($dep->hasChanged($this)) {
                    $this->delete($key, $filename, 'dependency');
                    return FALSE;
                }
            }

            return $item->value;
        }

        return FALSE;
    }

    public function add(string $key, $value, CacheDependency $dependency, DateTime $absoluteExpiration, DateInterval $slidingExpiration) {
        $filename = $this->getFilename($key);
        if (file_get_contents($filename) === FALSE) {
            file_put_contents($filename, $this->toData($key, $value, $dependency, $absoluteExpiration, $slidingExpiration));
            return $value;
        }
        return null;
    }

    public function insert(string $key, $value, CacheDependency $dependency, DateTime $absoluteExpiration, DateInterval $slidingExpiration) {
        $filename = $this->getFilename($key);
        file_put_contents($filename, $this->toData($key, $value, $dependency, $absoluteExpiration, $slidingExpiration));
        return $value;
    }

    public function remove(string $key) {
        $filename = $this->getFilename($key);
        if (file_get_contents($filename) !== FALSE) {
            $this->delete($key, $filename, 'user');
        }
    }

    public function getFilename($key) {
        return $this->cachePath . '/' . md5($key);
    }

    private function delete($key, $filename, $reason) {
        unlink($filename);
        if ($this->onRemove && is_callable($this->onRemove)) {
            call_user_func($this->onRemove, $key, $reason);
        }
    }

    private function toData(string $key, $value, CacheDependency $dependency, DateTime $absoluteExpiration, DateInterval $slidingExpiration) {
        $dependency->updateKeys($this); // we need to somehow update the keys with the values of this specific cache. Seems the best place, right after adding.
        return [
            'key' => $key,
            'value' => $value,
            'absoluteExpiration' => $absoluteExpiration,
            'slidingExpiration' => $slidingExpiration,
            'slidingExpirationEnd' => (new DateTime())->add($slidingExpiration),
            'dependency' => CacheDependency::serialize($dependency)
        ];
    }
}