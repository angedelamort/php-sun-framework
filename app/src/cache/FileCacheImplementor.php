<?php

namespace sunframework\cache;

use DateInterval;
use DateTime;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
            if (!mkdir($cachePath, 0755, TRUE)) {
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
        if (file_exists($filename)) {
            $item = $this->fromData($filename);
            if ($item->key !== $key) {
                throw new Exception("Key Collision between '$key' and '$item[key]'");
            }

            $now = new DateTime();
            if ($item->absoluteExpiration && $now >= $item->absoluteExpiration) {
                $this->delete($key, $filename, 'expired');
                return FALSE;
            }  else if ($item->slidingExpiration) {
                if ($now >= $item->slidingExpirationEnd) {
                    $this->delete($key, $filename, 'expired');
                    return FALSE;
                } else {
                    $item->slidingExpirationEnd = $now->add($item->slidingExpiration);
                    file_put_contents($filename, $this->toData($item->key, $item->value, $item->dependency, $item->absoluteExpiration, $item->slidingExpiration));
                }
            }

            if ($item->dependency) {
                if ($item->dependency->hasChanged($this)) {
                    $this->delete($key, $filename, 'dependency');
                    return FALSE;
                }
            }

            return $item->value;
        }

        return FALSE;
    }

    public function add(string $key, $value, ?CacheDependency $dependency, ?DateTime $absoluteExpiration, ?DateInterval $slidingExpiration) {
        $filename = $this->getFilename($key);
        if (!file_exists($filename)) {
            file_put_contents($filename, $this->toData($key, $value, $dependency, $absoluteExpiration, $slidingExpiration));
            return $value;
        }
        return FALSE;
    }

    public function insert(string $key, $value, ?CacheDependency $dependency, ?DateTime $absoluteExpiration, ?DateInterval $slidingExpiration) {
        $filename = $this->getFilename($key);
        file_put_contents($filename, $this->toData($key, $value, $dependency, $absoluteExpiration, $slidingExpiration));
        return $value;
    }

    public function remove(string $key) {
        $filename = $this->getFilename($key);
        if (file_exists($filename)) {
            $this->delete($key, $filename, 'user');
            return TRUE;
        }
        return FALSE;
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

    /**
     * @param $filename
     * @return CacheItem
     */
    private function fromData($filename) {
        return unserialize(file_get_contents($filename));
    }

    private function toData(string $key, $value, ?CacheDependency $dependency, ?DateTime $absoluteExpiration, ?DateInterval $slidingExpiration) {
        if ($dependency) {
            // we need to somehow update the keys with the values of this specific cache. Seems the best place, right after adding.
            $dependency->updateKeys($this);
        }

        return serialize(new CacheItem($key, $value, $absoluteExpiration, $slidingExpiration, $dependency));
    }

    public function clear() {
        $directories = new RecursiveDirectoryIterator($this->cachePath, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);
        $files = new RecursiveIteratorIterator($directories, RecursiveIteratorIterator::CHILD_FIRST );

        foreach ($files as $value ) {
            $value->isFile() ? unlink($value) : rmdir($value);
        }

        //rmdir( $this->cachePath );
    }
}