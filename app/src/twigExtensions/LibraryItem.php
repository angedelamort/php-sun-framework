<?php
namespace sunframework\twigExtensions;


class LibraryItem {

    /** @var $name string */
    public $name;
    /** @var $js array */
    public $js = [];
    /** @var $jsMin array */
    public $jsMin = [];
    /** @var $css array */
    public $css = [];
    /** @var $cssMin array */
    public $cssMin = [];

    //public $depends = [];


    public function __construct(string $name, $init = null) {
        $this->name = $name;

        if ($init != null) {
            $keys = ['js', 'jsMin', 'css', 'cssMin'/*, 'depends'*/];
            foreach ($keys as $key) {
                if (array_key_exists($key, $init)) {
                    $this->add($key, $init[$key]);
                }
            }
        }
    }

    public function addJs($value) {
        return $this->add('js', $value);
    }

    public function addJsMin($value) {
        return $this->add('jsMin', $value);
    }

    public function addCss($value) {
        return $this->add('css', $value);
    }

    public function addCssMin($value) {
        return $this->add('cssMin', $value);
    }

    /*public function addDepends($value) {
        return $this->add('depends', $value);
    }*/

    private function add(string $key, $value) {
        if (is_array($value)) {
            $this->$key = array_merge($this->$key, $value);
        } else if (is_string($value)) {
            $this->$key[] = $value;
        }
        return $this;
    }
}