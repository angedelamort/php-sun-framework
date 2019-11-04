<?php

namespace sunframework\i18n;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use sunframework\cache\Cache;
use sunframework\system\SunLogger;

/**
 * Class I18n
 * @package sunframework\i18n
 *
 * IETF language tag format for file naming
 */
final class I18n {

    /** @var string readonly*/
    private static $localePath;
    /** @var string readonly */
    private static $domain;
    /** @var string readonly */
    private static $defaultLanguage;
    /** @var array readonly */
    private static $localisationTable;
    /** @var SunLogger readonly */
    private static $logger;
    /** @var string readonly */
    private static $defaultValue;

    /** @var string  */
    public const DEFAULT_VALUE = '<key not found>';
    private const FILENAME_PATTERN = "/\/(?<domain>\w+)\.(?<languageTag>(?<isoCode>[a-z]{2})?(-(?<countryCode>[A-Z]{2}))?)\.php$/";
    private const LANGUAGE_PATTERN = "/^([a-z]{2})(-([A-Z]{2}))?$/";

    /**
     * Get the corresponding text from the key and optionally an option.
     *
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
        $defaultValue = I18n::$defaultValue;
        $domain = I18n::$domain;
        $replace = null;
        $language = $_SESSION['i18n-context'][$domain];

        if ($options) {
            if (is_string($options)) {
                $defaultValue = $options;
            }  else if (is_array($options)) {
                $replace = $options;
            } else if (is_object($options) && is_a($options, I18nOptions::class)) {
                if (property_exists($options, 'defaultValue') && is_string($options->defaultValue)) {
                    $defaultValue = $options->defaultValue;
                }
                if (property_exists($options, 'domain') && is_string($options->domain)) {
                    $domain = $options->domain;
                }
                if (property_exists($options, 'language') && is_string($options->language)) {
                    $language = $options->language;
                }
                if (property_exists($options, 'replace') && is_array($options->replace)) {
                    $replace = $options->replace;
                }
            }
        }

        if (isset(I18n::$localisationTable[$domain])) {
            if (isset(I18n::$localisationTable[$domain][$language])) {
                $langTable = I18n::$localisationTable[$domain][$language]['table'];
                if (isset($langTable[$key])) {
                    $value = $langTable[$key];

                    if ($replace) {
                        foreach ($replace as $key => $r) {
                            $value = str_replace("{" . "$key}", $r, $value); // stupid {$bug} -> cannot escape: https://bugs.php.net/bug.php?id=37263
                        }
                    }

                    return $value;
                } else {
                    I18n::$logger->warning("key '$key' is not defined. Did you add it to your table '$domain:$language'");
                }
            } else {
                I18n::$logger->warning("language '$language' doesn't exists in the domain '$domain'");
            }
        } else {
            I18n::$logger->warning("domain '$domain' doesn't exists");
        }

        return $defaultValue;
    }

    /**
     * I18n constructor.
     * Private since it's a static class.
     */
    private function __construct() {}

    /**
     * Initialize the module with the appropriate i18n values
     * @param string $localePath
     * @param string $defaultLanguage
     * @param string $domain
     * @param string $defaultValue
     * @return bool true if found from cache
     * @throws Exception
     */
    public static function init(string $localePath, string $defaultLanguage, string $domain, string $defaultValue = I18n::DEFAULT_VALUE) {
        I18n::$defaultValue = $defaultValue;
        I18n::$localePath = $localePath;
        I18n::$defaultLanguage = $defaultLanguage;
        I18n::$domain = $domain;
        I18n::$logger = new SunLogger('i18n');

        I18n::isValidLanguageTag($defaultLanguage);
        $ret = I18n::initTable();

        if (!isset($_SESSION['i18n-context'])) {
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                I18n::$logger->info("detecting languages: " . $_SERVER['HTTP_ACCEPT_LANGUAGE']);
                I18n::setUserLanguages(I18n::getUserDetectedLanguages());
            } else {
                I18n::$logger->info("No language detected in the header, will use default language $defaultLanguage");
                self::setLanguage($defaultLanguage);
            }
        }

        return $ret;
    }

    /**
     * @param string $language IETF language tag format for file naming: [language: ISO 639](-[country: ISO 3166‑1]). Country is optional
     */
    public static function setLanguage(string $language) {
        I18n::setUserLanguages([$language]);
    }

    /**
     * Validate if the language tag is well formed.
     * @param string $language
     * @return false|int
     */
    private static function isValidLanguageTag(string $language) {
        return preg_match(self::LANGUAGE_PATTERN, $language);
    }

    /**
     * @return bool true if found from cache
     * @throws Exception
     */
    private static function initTable() {
        if (Cache::global()) {
            I18n::$localisationTable = Cache::global()->get('i18n-cache-localization-table');
            if (I18n::$localisationTable) {
                return true;
            }
        }

        I18n::$localisationTable = [];
        // Read all the data
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(I18n::$localePath));
        foreach ($rii as $file) {
            if ($file->isDir()){
                continue;
            }

            if (preg_match(self::FILENAME_PATTERN, $file, $matches)) {
                $domain = $matches['domain'];
                $langTag = $matches['languageTag'];
                $isoCode = $matches['isoCode'];
                $countryCode = isset($matches['countryCode']) ? $matches['countryCode'] : null;

                if (!isset(I18n::$localisationTable[$domain])) {
                    I18n::$localisationTable[$domain] = [];
                }

                I18n::$localisationTable[$domain][$langTag] = [
                    'domain' => $domain,
                    'filename' => $file->getPathname(),
                    'languageTag' => $langTag,
                    'isoCode' => $isoCode,
                    'countryCode' => $countryCode,
                    'table' => include($file)
                ];
            } else {
                I18n::$logger->info("File '$file' doesn't follow the convention: domain.xx[-YY].php");
            }
        }

        // merge the tables.
        foreach (I18n::$localisationTable as &$domain) {
            foreach ($domain as &$table) {
                if ($table['countryCode'] && isset($domain[$table['isoCode']])) {
                    $table['table'] = array_merge($domain[$table['isoCode']]['table'], $table['table']);
                }
            }
        }

        if (Cache::global()) {
            Cache::global()->insert('i18n-cache-localization-table', I18n::$localisationTable);
        }

        return false;
    }

    private static function setUserLanguages($languages) {
        // MAke sure all tags are following the standard.
        foreach ($languages as $language) {
            I18n::isValidLanguageTag($language);
        }

        $userLanguages = [];
        foreach (I18n::$localisationTable as $domain => $table) {
            $found = false;
            foreach ($languages as $language) {
                if (isset($table[$language])) {
                    $userLanguages[$domain] = $language;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                if (!isset($table[I18n::$defaultLanguage])) {
                    I18n::$logger->error("No language defined for domain " .$domain  . "with locale " . I18n::$defaultLanguage);
                } else {
                    $userLanguages[$domain] = I18n::$defaultLanguage;
                }
            }
        }

        $_SESSION['i18n-context'] = $userLanguages;
    }

    private static function getUserDetectedLanguages() {
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
    }
}