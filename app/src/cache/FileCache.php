<?php

namespace sunframework\cache;


use Exception;

class FileCache extends Cache {

    /**
     * @param string $path
     * @param null $onRemove
     * @return FileCache
     * @throws Exception
     */
    public static function create(string $path, $onRemove = null) {
        return new FileCache(new FileCacheImplementor($path, $onRemove));
    }
}