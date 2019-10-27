<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use sunframework\cache\CacheFactory;
use sunframework\i18n\I18n;

final class I18nCacheTest extends TestCase {
    // TODO: setup - tear down...
    // todo: test the 3 different text method.
    // TODO: test the cache
    // TODO: test multiple domains
    // TODO: test inheritance.
    // TODO: test the default.

    public static function setUpBeforeClass() : void {
        CacheFactory::init(['db' => 'files', 'files-path' => __DIR__ . "/cache"]);
        I18n::init(__DIR__ . '/locale', 'en-US', 'default');
    }

    public static function tearDownAfterClass() : void {
        CacheFactory::instance()->clear();
    }

    public function testCache() {
        $this->assertEquals('US', I18n::text('country'));

        // call it again to hit the cache for the next one..
        $isFromCache = I18n::init(__DIR__ . '/locale', 'en-US', 'default');

        $this->assertTrue($isFromCache);
        $this->assertEquals('US', I18n::text('country'));
    }
}