<?php
namespace sunframework\user;

use \Psr\Http\Message\ServerRequestInterface;
use sunframework\system\StringUtil;

/**
 * Class UserSession
 * @package sunframework\user
 *
 * This is a default and simple implementation of the IUserSessionInterface.
 * You can create your own and initialize the AuthManager with it.
 *
 * This one is really straight forward and use 4 simple roles. We store the data
 * in the $_SESSION['user'] and in the $_SESSION['user_role'].
 */
class UserSession implements IUserSessionInterface {

    public const ROLE_ANONYMOUS = 0;
    public const ROLE_USER = 1;
    public const ROLE_POWER_USER = 2;
    public const ROLE_ADMIN = 3;

    /** @var int By default, we want all pages to at least with a USER role. */
    private $defaultRole = UserSession::ROLE_ANONYMOUS;
    /** @var array Array of [route, min user access] */
    private $allowedRoutes = [];


    public function getUserRoles() {
        return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : $this->defaultRole;
    }

    public function setUserRoles($role) {
        $_SESSION['user_role'] = $role;
    }

    public function getUser() {
        return isset($_SESSION['user']) ? $_SESSION['user'] : $this->defaultRole;
    }

    public function setUser($user) {
        $_SESSION['user'] = $user;
    }

    public function setDefaultRole(int $role) {
        $this->defaultRole = $role;
    }

    public function getDefaultRole() {
        return $this->defaultRole;
    }

    public function allow(string $baseRoute, int $minUserLevel) {
        $this->allowedRoutes[$baseRoute] = $minUserLevel;
    }

    public function validateUserAccess(ServerRequestInterface $request) {
        $route = $request->getAttribute('route');
        $path = $route->getPattern();
        $userRole = $this->getUserRoles();

        foreach ($this->allowedRoutes as $route => $minRole) {
            if (StringUtil::startsWith($path, $route)) {
                return $userRole >= $minRole;
            }
        }

        return $userRole >= $this->defaultRole;
    }

    public function validateUserRole($roles) {
        return $this->getUserRoles() >= $roles;
    }
}