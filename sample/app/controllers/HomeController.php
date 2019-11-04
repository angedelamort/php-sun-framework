<?php

namespace sample\controllers;


use sample\states\InitState;
use Slim\Http\Request;
use Slim\Http\Response;
use sunframework\route\IRoutable;
use sunframework\SunApp;

class HomeController implements IRoutable {
    public function registerRoute(SunApp $app) {
        $app->get('/', function(Request $request, Response $response, array $args) {
            return $this->view->render($response, 'home.twig');
        });

        $app->get('/phpinfo', function(Request $request, Response $response, array $args) {
            return phpinfo();
        });

        $app->get('/login', function(Request $request, Response $response, array $args) {
            return $this->view->render($response, 'login.twig');
        });

        $app->post('/login', function(Request $request, Response $response, array $args) use ($app) {
            $app->getAuthManager()->setUser([
                'name' => $request->getParsedBodyParam('name')
            ]);
            return $response->withRedirect('/');
        });

        $app->get('/logout', function(Request $request, Response $response, array $args) use ($app) {
            // Note: we could always destroy the session if we want to make sure nothing remains.
            $app->getAuthManager()->setUser(null);
            return $response->withRedirect('/');
        });
    }
}