<?php

namespace sample\controllers;


use Exception;
use sample\states\InitState;
use Slim\Http\Request;
use Slim\Http\Response;
use sunframework\route\IRoutable;
use sunframework\stateMachine\StateMachine;
use sunframework\stateMachine\StateMachineContext;
use sunframework\SunApp;

class StateController implements IRoutable {
    const SESSION_KEY = 'sm-state';

    public function registerRoute(SunApp $app) {
        $app->get('/state', function(Request $request, Response $response, array $args) {
            $sm = self::createStateMachine();
            $state = $sm->getState();
            $context = new StateMachineContext($request, $args, []);
            return $this->view->render($response, $state->getView(), $state->getModel($context));
        });

        $app->post('/state', function(Request $request, Response $response, array $args) {
            $sm = self::createStateMachine();
            $context = new StateMachineContext($request, $args, []);
            if ($sm->step($context)) {
                self::setCurrentState($sm->getState()->getName());
            }
            $state = $sm->getState();
            return $this->view->render($response, $state->getView(), $state->getModel($context));
        });
    }

    private static function getCurrentState() {
        if (isset($_SESSION[self::SESSION_KEY])) {
            return $_SESSION[self::SESSION_KEY];
        }
        return InitState::name();
    }

    private static function setCurrentState(string $state) {
        $_SESSION[self::SESSION_KEY] =$state;
    }

    /**
     * @return StateMachine
     * @throws Exception
     */
    private static function createStateMachine() {
        return new StateMachine(self::getCurrentState(), 'sample\states');
    }
}