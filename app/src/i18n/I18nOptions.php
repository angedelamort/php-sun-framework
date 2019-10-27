<?php

namespace sunframework\i18n;


final class I18nOptions {

    /**
     * I18nOptions constructor.
     * @param string|null $defaultValue
     * @param string|null $language
     * @param array|null $replace
     * @param string|null $domain
     */
    public function __construct(string $defaultValue = null, string $language = null, array $replace = null, string $domain = null) {
        $this->defaultValue = $defaultValue;
        $this->language = $language;
        $this->replace = $replace;
        $this->domain = $domain;
    }

    /** @var string */
    public $defaultValue;
    /** @var string IETF language tag format for file naming: [language: ISO 639](-[country: ISO 3166‑1]). Country is optional */
    public $language;
    /** @var array */
    public $replace;
    /** @var string */
    public $domain;
}