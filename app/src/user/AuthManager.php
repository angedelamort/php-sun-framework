<?php
namespace sunframework\user;

use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;

class AuthManager {

    private $failureCallable; // TODO: add a setFailureCallback
    private $roleValidator;

    /**
     * Construct the Authentication Manager.
     * @param IRoleValidatorInterface $roleValidator
     */
    public function __construct(IRoleValidatorInterface $roleValidator) {
        $this->roleValidator = $roleValidator;
    }

    public function allow(StringUtil $baseRoute, int $minUserLevel) {
        $this->roleValidator->allow($baseRoute, $minUserLevel);
    }

    public function setUserRole(int $role) {
        $this->roleValidator->setUserRole($role);
    }

    public function getRoleValidator() {
        return $this->roleValidator;
    }

    /**
     * Invoke middleware
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return mixed
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next) {
         if (!$this->roleValidator->validateUserAccess($request)) {
            $failureCallable = $this->getFailureCallable();
            return $failureCallable($request, $response, $next);
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
                $body->write('You don\'t have access to this page.');
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
}