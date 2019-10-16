<?php
namespace sunframework\twigExtensions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Response;

/**
 * @see https://github.com/slimphp/Slim-Csrf
 * TODO: change to \Twig\Extension\GlobalsInterface
 */
class CsrfExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /**
     * @var \Slim\Csrf\Guard
     */
    protected $csrf;
    
    public function __construct(\Slim\Csrf\Guard $csrf) {
        $this->csrf = $csrf;
    }

    public function getGlobals() {
        // CSRF token name and value
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