<?php
namespace sunframework\user;

use \Psr\Http\Message\ServerRequestInterface;
use sunframework\system\StringUtil;

class RoleValidator implements IRoleValidatorInterface {

    public const ROLE_ANONYMOUS = 0;
    public const ROLE_USER = 1;
    public const ROLE_POWER_USER = 2;
    public const ROLE_ADMIN = 3;

    // todo: add permissions

    /** @var int By default, we want all pages to at least with a USER role. */
    private $defaultRole = RoleValidator::ROLE_ANONYMOUS;
    /** @var array Array of [route, min user access] */
    private $allowedRoutes = [];


    public function getUserRole() {
        return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : $this->defaultRole;
    }

    public function setUserRole(int $role) {
        $_SESSION['user_role'] = $role;
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
        $userRole = $this->getUserRole();

        foreach ($this->allowedRoutes as $route => $minRole) {
            if (StringUtil::startsWith($path, $route)) {
                return $userRole >= $minRole;
            }
        }

        return $userRole >= $this->defaultRole;
    }

    public function validateUserRole($role) {
        return $this->getUserRole() >= $role;
    }
}