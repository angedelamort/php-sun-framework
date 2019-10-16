<?php

namespace sunframework\twigExtensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class FormErrorExtension
 * Use the $_SESSION['form-errors'] field.
 */
class FormErrorExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('fieldState', [$this, 'fieldState']),
            new TwigFunction('fieldErrors', [$this, 'fieldErrors'], ['is_safe' => ['html']]),
            new TwigFunction('formValidation', [$this, 'formValidation'], ['is_safe' => ['html']]),

            // TODO: move to somewhere else.
            new TwigFunction('numberToWords', [$this, 'numberToWords'], ['is_safe' => ['html']])
        ];
    }

    // Implement this method if I need more than that.
    // https://stackoverflow.com/questions/11500088/php-express-number-in-words
    public function numberToWords(int $number) {
        static $list1 = array('zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven',
            'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'
        );

        return $list1[$number];
    }

    /**
     * @param $key string
     * @return string 'error' on error and empty when ok.
     *
     * NOTE: could add warning later on...
     */
    public function fieldState($key) {
        if (isset($_SESSION['form-errors']) && isset($_SESSION['form-errors'][$key]))
            return 'error';
        return '';
    }

    public function formValidation($schema) {
        return "data-form-validation=\"$schema\"";
    }

    // TODO: implement this method appropriately.
    public function fieldErrors() {
        $html = "<div class=\"ui message error\"></div>";
        if (isset($_SESSION['form-errors']) && count($_SESSION['form-errors']) > 0)
            $html .= "<div class=\"ui message red\"><div class=\"header\">Field errors</div><p>An error has occurred in the data validation server side. Please retry.</p></div>";

        return $html;
    }
}