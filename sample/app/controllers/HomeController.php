<?php

namespace sample\controllers;


use Slim\Http\Request;
use Slim\Http\Response;
use sunframework\route\IRoutable;
use sunframework\SunApp;

class HomeController implements IRoutable {
    public function registerRoute(SunApp $app) {
        $app->get('/', function(Request $request, Response $response, array $args) {
            return $this->view->render($response, 'test.twig', [
                'user' => 'John Doe'
            ]);
        });

        $app->get('/phpinfo', function(Request $request, Response $response, array $args) {
            return phpinfo();
        });
    }
}