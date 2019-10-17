<?php
namespace sunframework\twigExtensions;

use Slim\Csrf\Guard;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * register the the csrf token as a global variable
 * @see https://github.com/slimphp/Slim-Csrf
 */
class CsrfExtension extends AbstractExtension  implements GlobalsInterface
{
    /** @var $csrf Guard  */
    protected $csrf;
    
    public function __construct(Guard $csrf) {
        $this->csrf = $csrf;
    }

    public function getGlobals() {
        $csrfNameKey = $this->csrf->getTokenNameKey();
        $csrfValueKey = $this->csrf->getTokenValueKey();
        $csrfName = $this->csrf->getTokenName();
        $csrfValue = $this->csrf->getTokenValue();
        
        return [
            'csrf'   => [
                'keys' => [
                    'name'  => $csrfNameKey,
                    'value' => $csrfValueKey
                ],
                'name'  => $csrfName,
                'value' => $csrfValue
            ]
        ];
    }

    public function getName() {
        return 'slim/csrf';
    }
}