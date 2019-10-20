<?php

namespace sample\states;


use sunframework\stateMachine\BaseState;
use sunframework\stateMachine\StateMachineContext;
use sunframework\stateMachine\Transition;

class FinalState extends BaseState {

    public function __construct() {
        parent::__construct();

        $failTransition = new Transition(InitState::name(), [$this, 'newGame']);
        $this->addTransition($failTransition);
    }

    public function getView() {
        return 'success.twig';
    }

    public function getModel(StateMachineContext $context) {
        return $_SESSION[InitState::SESSION_KEY];
    }

    public function newGame(StateMachineContext $context) {
        unset($_SESSION[InitState::SESSION_KEY]);
    }
}