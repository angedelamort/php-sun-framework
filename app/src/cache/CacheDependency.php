<?php

namespace sunframework\cache;

use Exception;
use InvalidArgumentException;

/**
 * Class CacheDependency
 * @package sunframework\cache
 *
 * Establishes a dependency relationship between an item stored in an Cache object and a file, cache key,
 * an array of either. The CacheDependency class monitors the dependency relationships so that when any of
 * them changes, the cached item will be automatically removed.
 *
 * Note: For now, cannot watch directories
 */
class CacheDependency {

    /** @var array */
    private $files = null;

    /** @var array */
    private $keys = null;

    /**
     * CacheDependency constructor.
     * @param $files array|string|null
     * @param $cacheKeys array|string|null
     * @throws InvalidArgumentException
     */
    public function __construct($files, $cacheKeys = null) {
        if (is_string($files)) {
            $this->initFiles([$files]);
        } else if (is_array($files)) {
            $this->initFiles($files);
        } else if ($files != null) {
            throw new InvalidArgumentException("files");
        }

        if (is_string($cacheKeys)) {
            $this->keys = [$cacheKeys];
        } else if (is_array($cacheKeys)) {
            $this->keys = $cacheKeys;
        } else if ($cacheKeys != null) {
            throw new InvalidArgumentException("cacheKeys");
        }
    }

    public function hasChanged(ICacheImplementor $cache) {
        if ($this->files != null) {
            foreach ($this->files as $fileItem) {
                $filename = $fileItem['filename'];
                $check = file_exists($filename) ? filemtime($filename) : 0;
                if ($check != $fileItem['modified']) {
                    return true;
                }
            }
        }

        if ($this->keys != null) {
            foreach ($this->keys as $keyItem) {
                if ($keyItem['value'] != $cache->get($keyItem['key'])) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function serialize(CacheDependency $dependency) {
        return ['files' => $dependency->files, 'keys' => $dependency->keys];
    }

    public static function deserialize(array $value) {
        $cd = new CacheDependency(null, null);
        $cd->files = $value['files'];
        $cd->keys = $value['keys'];

        return $cd;
    }

    private function initFiles(array $filenames) {
        $this->files = [];
        foreach ($filenames as $filename) {
            $this->files[] = [
                'filename' => $filename,
                'modified' => file_exists($filename) ? filemtime($filename) : 0
            ];
        }
    }

    public function initKeys($keys) {
        $this->keys = [];
        foreach ($keys as $key) {
            $this->files[] = [
                'key' => $key,
                'value' => null
            ];
        }
    }

    public function updateKeys(ICacheImplementor $cache) {
        if ($this->keys != null) {
            foreach ($this->keys as &$keyItem) {
                $keyItem['value'] = $cache->get($keyItem['key']);
            }
        }
    }
}