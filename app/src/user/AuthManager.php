<?php
namespace sunframework\user;

use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;

/**
 * Class AuthManager
 * @package sunframework\user
 *
 * Manage the authentication for a user. This is a really simple implementation.
 * You can use the default UserSession if you want something out of the box.
 *
 * The AuthManager doesn't handle the persistence. You will need a separate module to load the
 * user information and store it in the session using the IUserSessionInterface::setUser and
 * IUserSessionInterface::setUserRole. (You could store it in a txt file, a mySQL DB or even in
 * Amazon Dynamo DB)
 */
class AuthManager {

    private $failureCallable;
    private $roleValidator;

    /**
     * Construct the Authentication Manager.
     * @param IUserSessionInterface $roleValidator
     */
    public function __construct(IUserSessionInterface $roleValidator) {
        $this->roleValidator = $roleValidator;
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

    public function setFailureCallable($failureCallable) {
        $this->failureCallable = $failureCallable;
        return $this;
    }

    public function validateUserRole($role) {
        return $this->roleValidator->validateUserRole($role);
    }

    public function allow(string $baseRoute, int $minUserLevel) {
        return $this->roleValidator->allow($baseRoute, $minUserLevel);
    }

    public function getUser()
    {
        return $this->roleValidator->getUser();
    }

    public function setUser($user) {
        return $this->roleValidator->setUser($user);
    }

    public function getUserRoles() {
        return $this->roleValidator->getUserRoles();
    }

    public function setUserRoles($role) {
        return $this->roleValidator->setUserRoles($role);
    }
}