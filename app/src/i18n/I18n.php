<?php

namespace sunframework\i18n;

// TODO: implement a nice default mechanism. If en-us not found, try to find 'en'.
final class I18n {

    /** @var string */
    private static $localePath;
    /** @var string */
    private static $domain;
    /** @var string */
    private static $defaultLanguage;
    /** @var string */
    private static $currentLanguage;
    /** @var array */
    private static $defaultLocalisationTable;
    /** @var array */
    private static $currentLocalisationTable;
    /** @var array */
    private static $localisationTable;

    private const DEFAULT_VALUE = 'key not found.';

    /**
     * Options:
     * [
     *      'defaultValue' => defaultValue to return if a translation was not found,
     *      'language' => override language to use for this,
     *      'replace' => array with key/value that will correspond to the object (interpolation).
     * ]
     * Examples:
     *   text('key') => 'myValue {name}'
     *   text('keyError', 'default') => 'default'
     *   text('key', ['name' => 'hello']) => 'myValue hello'
     *   text('key', (object)['language' => 'fr_ca']) => 'maValeur {name}'
     *
     * @param string $key
     * @param $options mixed Can be a default value (string) or replace (array) and options (object).
     * @return string the resulting string.
     */
    public static function text(string $key, $options = null) {

        if ($options == null) {
            return (isset(I18n::$localisationTable[$key])) ? I18n::$localisationTable[$key] : I18n::DEFAULT_VALUE;
        }

        $defaultValue = I18n::DEFAULT_VALUE;
        $replace = null;

        if (is_string($options)) {
            $defaultValue = $options;
        } else if (is_array($options) && count($options) > 0) {
            $replace = $options;
        } else if (is_object($options)) {
            if (property_exists($options, 'defaultValue')) {
                $defaultValue = $options->defaultValue;
            }
            if (property_exists($options, 'replace')&& count($options->replace) > 0) {
                $replace = $options->replace;
            }
        }

        if (isset(I18n::$localisationTable[$key])) {
            $value = I18n::$localisationTable[$key];
            if ($replace) {
                foreach ($replace as $key => $r) {
                    $value = str_replace("{" . "$key}", $r, $value); // stupid {bug} -> cannot escape: https://bugs.php.net/bug.php?id=37263
                }
            }

            return $value;
        }

        return $defaultValue;
    }

    /**
     * Initialize the module with the appropriate i18n values
     * @param string $localePath
     * @param string $defaultLanguage
     * @param string $domain
     */
    public static function init(string $localePath, string $defaultLanguage, string $domain) {
        I18n::$localePath = $localePath;
        I18n::$defaultLanguage = I18n::$currentLanguage = $defaultLanguage;
        I18n::$domain = $domain;
        I18n::$localisationTable = I18n::$defaultLocalisationTable  = I18n::$currentLocalisationTable = I18n::getTable($defaultLanguage);

        // TODO:
        // 1. parse all files in the directory and map them properly in a map
        // 2. try to find the default language -> if not there throw an exception.
        // 3. try to get the user language using header and match it.
        // 4. save the user language in a cookie so we don't do all this code all over again.
        // 5. create a cache with a json by configuration instead of in-memory-cache -> will be faster for fetching.
        //  5.1. Use dates to check if cache older than files?
        // https://packagist.org/packages/phpfastcache/phpfastcache


        /*if (!isset($_SESSION['_current-language'])) {
            $this->logger->info("detecting languages: " . $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $languages = $this->getLanguages();
            if (count($languages) > 0) {
                $lang = array_key_first($languages);
                // TODO: might be interesting to have in the i18n module a list of supported languages? Could default on other languages.
                $_SESSION['current-language'] = $lang;
                $this->logger->info("setting language to $lang");
            }
        }*/
    }

    /*private function getLanguages() {
        $languages = [];

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);

            if (count($matches[1])) {
                // map all in array
                $languages = array_combine($matches[1], $matches[4]);
                // and fill the empty one
                foreach ($languages as $language => $val) {
                    if ($val === '') {
                        $languages[$language] = 1;
                    }
                }
                // let's sort them
                arsort($languages, SORT_NUMERIC);
            }
        }

        return $languages;
    }*/

    public static function setLanguage(string $language) {
        I18n::$currentLanguage = $language;
        I18n::$currentLocalisationTable = I18n::getTable($language);
    }

    private static function getTable($language) {
        return include(I18n::$localePath . DIRECTORY_SEPARATOR . I18n::$domain . '.' . $language . '.php');
    }
}