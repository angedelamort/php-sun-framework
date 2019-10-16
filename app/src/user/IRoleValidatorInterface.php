<?php
namespace sunframework\user;

use \Psr\Http\Message\ServerRequestInterface;

interface IRoleValidatorInterface {

    /**
     * return 1 if user have access, 0 to deny.
     *
     * @remark you can get more information on the ServerRequestInterface and
     *         how to use the parameters.
     * https://www.slimframework.com/docs/v3/cookbook/retrieving-current-route.html
     * @param ServerRequestInterface $request
     */
    public function validateUserAccess(ServerRequestInterface $request);

    public function setUserRole(int $role);

    public function allow(string $baseRoute, int $minUserLevel);
}