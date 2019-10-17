<?php
namespace sunframework\user;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class UserTwigExtension extends AbstractExtension implements GlobalsInterface{
    private $authManager;

    public function __construct(AuthManager $authManager) {
        $this->authManager = $authManager;
    }

    public function getGlobals() {
        $user = ['user' => null];

        if (isset($_SESSION['user'])) {
            $user['user'] = $_SESSION['user'];
        }
        if (isset($_SESSION['user_role'])) {
            $user['userRole'] = $_SESSION['user_role'];
        }
        return $user;
    }

    public function getFunctions() {
        return [new TwigFunction('userIsRole', [$this, 'userIsRole'])];
    }

    public function userIsRole($role) {
        $this->authManager->getRoleValidator()->validateUserRole($role);
    }
}