<?php
namespace sunframework\route;

use sunframework\SunApp;

interface IRoutable {
    /**
     * Register the routes of your controller.
     * @param SunApp $app The current slim app
     */
    public function registerRoute(SunApp $app);
}