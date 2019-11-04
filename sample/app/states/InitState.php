<?php

namespace sample\states;


use sample\controllers\StateController;
use sunframework\stateMachine\BaseState;
use sunframework\stateMachine\StateMachineContext;
use sunframework\stateMachine\Transition;

class InitState extends BaseState {

    private $word = 'hello';
    const SESSION_KEY = 'state-game';

    public function __construct() {
        parent::__construct();

        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [
                'word' => str_split($this->word),
                'guess' => array_fill(0, strlen($this->word), '_'),
                'guessCount' => 0
            ];
        }

        $this->initStateMachine();
    }

    public function getView() {
        return 'hangman.twig';
    }

    public function getModel(StateMachineContext $context) {
        return $_SESSION[self::SESSION_KEY];
    }

    public function increaseGuess(StateMachineContext $context) {
        $_SESSION[self::SESSION_KEY]['guessCount']++;
        // Note: could also add the character that failed... but later
    }

    public function setLetter(StateMachineContext $context) {
        $letter = $context->getRequest()->getParsedBodyParam('letter');
        $state = $_SESSION[self::SESSION_KEY];
        for ($i = 0; $i < count($state['word']); $i++) {
            if ($state['word'][$i] === $letter) {
                $state['guess'][$i] = $letter;
            }
        }

        $_SESSION[self::SESSION_KEY] = $state;
    }

    public static function reset() {
        unset($_SESSION[self::SESSION_KEY]);
        unset($_SESSION[StateController::SESSION_KEY]);
    }

    private static function isNewLetter($letter) {
        $state = $_SESSION[self::SESSION_KEY];
        for ($i = 0; $i < count($state['word']); $i++) {
            if ($state['word'][$i] === $letter && $state['guess'][$i] === '_') {
                return true;
            }
        }
        return false;
    }

    private static function isCompleted($letter) {
        $state = $_SESSION[self::SESSION_KEY];
        for ($i = 0; $i < count($state['word']); $i++) {
            if ($state['word'][$i] !== $letter && $state['guess'][$i] === '_') {
                return false;
            }
        }
        return true;
    }

    private function initStateMachine(): void
    {
        $resetTransition = new Transition(InitState::name(), [$this, 'reset'], 'completed');
        $resetTransition->addCondition(function (StateMachineContext $context) {
            return $context->getRequest()->getParsedBodyParam('action') === 'Reset';
        });
        $this->addTransition($resetTransition);

        $finalTransition = new Transition(FinalState::name(), [$this, 'setLetter'], 'completed');
        $finalTransition->addCondition(function (StateMachineContext $context) {
            return self::isCompleted($context->getRequest()->getParsedBodyParam('letter'));
        });
        $this->addTransition($finalTransition);

        $successTransition = new Transition(InitState::name(), [$this, 'setLetter'], 'goodGuess');
        $successTransition->addCondition(function (StateMachineContext $context) {
            return self::isNewLetter($context->getRequest()->getParsedBodyParam('letter'));
        });
        $this->addTransition($successTransition);

        $failTransition = new Transition(InitState::name(), [$this, 'increaseGuess'], 'badGuess');
        $this->addTransition($failTransition);
    }
}