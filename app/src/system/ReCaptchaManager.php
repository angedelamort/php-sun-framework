<?php
namespace sunframework\system;

use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;

class ReCaptchaManager {

    private $secret;

    /**
     * Construct the Authentication Manager.
     * @param StringUtil Secret to call Google ReCaptcha.
     */
    public function __construct($secret) {
        $this->secret = $secret;
    }

    /**
     * Invoke middleware
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next) {

        $body = $request->getParsedBody();

        if (isset($body['reCaptcha'])) {
            $token = $body['reCaptcha'];
            $recaptcha = new \ReCaptcha\ReCaptcha($secret);
            $resp = $recaptcha->setExpectedHostname('sauleil.com')
                  ->setExpectedAction('login')
                  ->setScoreThreshold(0.5)
                  ->verify($token, $this->get_client_ip());
            if (!$resp->isSuccess()) {
                // $errors = $resp->getErrorCodes();
                $failureCallable = $this->getFailureCallable();
                return $failureCallable($request, $response, $next);
            }
        }

        return $next($request, $response);
     }

     /**
     * Getter for failureCallable
     *
     * @return callable|\Closure
     */
     public function getFailureCallable() {
        
        if (is_null($this->failureCallable)) {
            $this->failureCallable = function (ServerRequestInterface $request, ResponseInterface $response, $next) {
                $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
                $body->write('You were detected as a bot.');
                return $response->withStatus(400)->withHeader('Content-type', 'text/plain')->withBody($body);
            };
        }
        return $this->failureCallable;
    }

    public function setFailureCallable($failureCallable)
    {
        $this->failureCallable = $failureCallable;
        return $this;
    }

    // Function to get the client IP address
    private function get_client_ip() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
           $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
}