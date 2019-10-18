<?php
namespace sunframework\user;

use \Psr\Http\Message\ServerRequestInterface;

interface IUserSessionInterface {

    /**
     * return 1 if user have access, 0 to deny.
     *
     * @remark you can get more information on the ServerRequestInterface and
     *         how to use the parameters.
     * https://www.slimframework.com/docs/v3/cookbook/retrieving-current-route.html
     * @param ServerRequestInterface $request
     */
    public function validateUserAccess(ServerRequestInterface $request);

    public function validateUserRole($role); // TODO: could be a list

    public function allow(string $baseRoute, int $minUserLevel);

    /**
     * @return mixed Return the user structure of the implementor. Null if not set.
     */
    public function getUser();

    /**
     * Set any structure you want in the implementor and it will be passed to you.
     * Set it to null to remove the current logged user.
     * @param $user
     * @return mixed
     */
    public function setUser($user);

    /**
     * @return mixed Get the user roles you have defined.
     */
    public function getUserRoles();

    /**
     * Set the appropriate user roles defined in the implementor.
     * @param $roles
     * @return mixed
     */
    public function setUserRoles($roles);
}