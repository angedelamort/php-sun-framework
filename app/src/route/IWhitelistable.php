<?php
namespace sunframework\route;

interface IWhitelistable {
    /**
     * Get the list of route white listed so they won't be checked by the CSRF plugin if enabled.
     * @return array list of strings that will be checked with startsWith().
     * TODO: can return array strings (startsWith) and regex object.
     */
    public function getWhitelistedRoutes();
}