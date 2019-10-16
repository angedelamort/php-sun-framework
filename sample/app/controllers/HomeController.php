<?php

namespace sample\controllers;


use Slim\Http\Request;
use Slim\Http\Response;
use sunframework\route\IRoutable;

class HomeController implements IRoutable {
    public function registerRoute($app) {
        $app->get('/', function(Request $request, Response $response, array $args) {
            return $this->view->render($response, 'test.twig', [
                'user' => 'John Doe'
            ]);
        });
    }
}