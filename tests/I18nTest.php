<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use sunframework\i18n\I18n;
use sunframework\i18n\I18nOptions;

final class I18nTest extends TestCase {
    public static function setUpBeforeClass() : void {
        I18n::init(__DIR__ . '/locale', 'en-US', 'default');
    }

    public static function tearDownAfterClass() : void {
    }

    public function testKeyOnly() {
        $this->assertEquals('US', I18n::text('country'));
        $this->assertEquals(I18n::DEFAULT_VALUE, I18n::text('not-defined'));
    }

    public function testKeyOnlyTextOptionsWithString() {
        $this->assertEquals('myDefault', I18n::text('not-defined', 'myDefault'));
    }

    public function testKeyOnlyTextOptionsWithArrayReplace() {
        $value = I18n::text('replace', [
            'a' => 'one',
            'b' => '1',
            'c' => 'ii',
        ]);
        $this->assertEquals('one + 1 = ii', $value);
    }

    public function testOptions() {
        $options = new I18nOptions();
        $options->defaultValue = 'myDefault';
        $this->assertEquals('myDefault', I18n::text('not-defined', $options));

        $options->replace = ['a' => '1', 'b' => '1', 'c' => '2'];
        $this->assertEquals('1 + 1 = 2', I18n::text('replace', $options));

        $options->language = 'en';
        $this->assertEquals('none', I18n::text('country', $options));

        $options->language = 'en-US';
        $options->domain = 'special';
        $this->assertEquals('bar', I18n::text('foo', $options));
    }
}