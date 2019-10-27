<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use sunframework\cache\Cache;
use sunframework\cache\CacheDependency;
use sunframework\cache\FileCache;

final class CacheTest extends TestCase {

    /** @var Cache */
    private static $cache;
    private static $tempFile;

    public static function setUpBeforeClass() : void {
        self::$cache = FileCache::create(__DIR__ . '/cache');

        self::$tempFile = __DIR__ . '/cache/foo.bar';
        file_put_contents(self::$tempFile, "Hello World!");
    }

    public static function tearDownAfterClass() : void {
        self::$cache->clear();
    }

    public function testAdd(): void {
        $ret = self::$cache->add("testAdd", "myValue");
        $this->assertNotEquals(FALSE, $ret);

        $value = self::$cache->get("testAdd");
        $this->assertEquals("myValue", $value);
    }

    public function testDoubleAdd() : void {
        $ret = self::$cache->add("testDoubleAdd", "myValue");
        $this->assertNotEquals(FALSE, $ret);

        $ret = self::$cache->add("testDoubleAdd", "-------");
        $this->assertEquals(FALSE, $ret);

        $value = self::$cache->get("testAdd");
        $this->assertEquals("myValue", $value);
    }

    public function testInsert() : void {
        $ret = self::$cache->insert("testInsert", "myValue");
        $this->assertNotEquals(FALSE, $ret);

        $value = self::$cache->get("testInsert");
        $this->assertEquals("myValue", $value);
    }

    public function testDoubleInsert() : void {
        $ret = self::$cache->insert("testDoubleInsert", "myValue");
        $this->assertNotEquals(FALSE, $ret);

        $ret = self::$cache->insert("testDoubleInsert", "myNewValue");
        $this->assertNotEquals(FALSE, $ret);

        $value = self::$cache->get("testDoubleInsert");
        $this->assertEquals("myNewValue", $value);
    }

    public function testRemove() : void {
        $ret = self::$cache->add("TestRemove", "myValue");
        $this->assertNotEquals(FALSE, $ret);

        self::$cache->remove("TestRemove");

        $value = self::$cache->get("TestRemove");
        $this->assertFalse($value);
    }

    public function testAbsoluteExpiration() : void {
        $ret = self::$cache->add("TestAbsoluteExpiration", "myValue", null, (new DateTime())->add(new DateInterval("PT1S")));
        $this->assertNotEquals(FALSE, $ret);

        $value = self::$cache->get("TestAbsoluteExpiration");
        $this->assertEquals("myValue", $value);

        sleep(1);

        $value = self::$cache->get("TestAbsoluteExpiration");
        $this->assertFalse($value);
    }

    public function testSlidingExpiration() : void {
        $ret = self::$cache->add("TestSlidingExpiration", "myValue", null, null, new DateInterval("PT2S"));
        $this->assertNotEquals(FALSE, $ret);

        $value = self::$cache->get("TestSlidingExpiration");
        $this->assertEquals("myValue", $value);

        sleep(1);

        $value = self::$cache->get("TestSlidingExpiration");
        $this->assertEquals("myValue", $value);

        sleep(2);

        $value = self::$cache->get("TestSlidingExpiration");
        $this->assertFalse($value);
    }

    public function testKeyCacheDependency() : void {
        self::$cache->add("testKeyCacheDependency-d", "uninteresting");
        self::$cache->add("testKeyCacheDependency", "myValue", new CacheDependency(null, 'testKeyCacheDependency-d'));

        $value = self::$cache->get("testKeyCacheDependency");
        $this->assertEquals("myValue", $value);

        self::$cache->insert("testKeyCacheDependency-d", "uninteresting-2");
        $value = self::$cache->get("testKeyCacheDependency");
        $this->assertFalse($value);
    }

    public function testFileCacheDependency() : void {
        self::$cache->add("testFileCacheDependency", "myValue", new CacheDependency(self::$tempFile));
        $value = self::$cache->get("testFileCacheDependency");
        $this->assertEquals("myValue", $value);

        file_put_contents(self::$tempFile, "New Data in File!");

        $value = self::$cache->get("testFileCacheDependency");
        $this->assertFalse($value);
    }
}