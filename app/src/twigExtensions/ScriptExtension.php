<?php

namespace sunframework\twigExtensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class ScriptExtension
 * @package sunframework\system\twigExtensions
 */
class ScriptExtension extends AbstractExtension
{
    private static $library = [];
    private static $isMin = true;

    public static function addLibrary(LibraryItem $item) {
        self::$library[$item->name] = $item;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('css', [$this, 'css'], ['is_safe' => ['html'], 'is_variadic' => true]),
            new TwigFunction('js', [$this, 'js'], ['is_safe' => ['html'], 'is_variadic' => true])
        ];
    }

    public function css(array $args = []) {
        return self::includes('css', '<link rel="stylesheet" href="#" />', $args);
    }

    public function js(array $args = []) {
        return self::includes('js', '<script src="#"></script>', $args);
    }

    private static function includes(string $key, string $template, array $args = []) {
        $includes = "";
        $keyAlt = $key;
        if (self::$isMin) {
            $key .= 'Min';
        } else {
            $keyAlt .= 'Min';
        }

        foreach ($args as $arg) {
            if (isset(self::$library[$arg])) { // library exists!
                /** @var LibraryItem $lib */
                $lib = self::$library[$arg];
                if (count($lib->$key) > 0) {
                    foreach ($lib->$key as $item) {
                        $includes .= str_replace('#', $item, $template);
                    }
                } else if (count($lib->$keyAlt) > 0) {
                    foreach ($lib->$keyAlt as $item) {
                        $includes .= str_replace('#', $item, $template);
                    }
                }
            } else {
                $includes .= str_replace('#', $arg, $template);
            }
        }

        return $includes;
    }
}