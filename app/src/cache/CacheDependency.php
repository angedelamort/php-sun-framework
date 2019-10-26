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
        if ($this->keys) {
            $this->initKeys($this->keys);
        }
    }

    private function getFileInformation($file) {
        if (file_exists($file)) {
            return [
                "exists" => true,
                "modified" => filemtime($file),
                "fileSize" => filesize($file),
                "hash" => md5_file($file),
                "filename" => $file
            ];
        }

        return [
            "exists" => false,
            "modified" => 0,
            "fileSize" => 0,
            "hash" => 0,
            "filename" => $file
        ];
    }

    private function fileHasChanged($item) {
        return $item['exists'] !== file_exists($item['filename']) ||
            $item['modified'] !== filemtime($item['filename']) ||
            $item['fileSize'] !== filesize($item['filename']) ||
            $item['hash'] !== md5_file($item['filename']);
    }

    public function hasChanged(ICacheImplementor $cache) {
        if ($this->files != null) {
            foreach ($this->files as $fileItem) {
                return $this->fileHasChanged($fileItem);
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

    public static function serialize(?CacheDependency $dependency) {
        if ($dependency)
            return ['files' => $dependency->files, 'keys' => $dependency->keys];
        return null;
    }

    public static function deserialize($value) {
        if ($value) {
            $value = json_decode(json_encode($value), true); // TODO: parent should be an array as well.
            $cd = new CacheDependency(null, null);
            $cd->files = (array)$value['files'];
            $cd->keys = (array)$value['keys'];

            return $cd;
        }

        return null;
    }

    private function initFiles(array $filenames) {
        $this->files = [];
        foreach ($filenames as $filename) {
            $this->files[] = $this->getFileInformation($filename);
        }
    }

    private function initKeys($keys) {
        $this->keys = [];
        foreach ($keys as $key) {
            $this->keys[] = [
                'key' => $key,
                'value' => null
            ];
        }
    }

    public function updateKeys(ICacheImplementor $cache) {
        if ($this->keys != null) {
            foreach ($this->keys as &$keyItem) {
                if (isset($keyItem['key'])) {
                    $keyItem['value'] = $cache->get($keyItem['key']);
                }
            }
        }
    }
}