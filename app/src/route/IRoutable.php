<?php
namespace sunframework\route;

interface IRoutable {
    /**
     * Register the routes of your controller.
     * @param \Slim\App $app The current slim app
     */
    public function registerRoute($app);
}