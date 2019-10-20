<?php

namespace sunframework\stateMachine;


use Exception;
use Monolog\Logger;

/**
 * Class StateMachine
 * @package premplay\stateMachine
 *
 * Rendering State Machine.
 *
 * This is an optimized version of a state machine. This was written exclusively to handle
 * the case of an instantiation of a new state machine on every call. Also, the generalization
 * was simplified for this case only. Also, for simplification, I do late state initialization since I don't want
 * to create all the state machine. The reason I use string instead of a state in the transition.
 *
 * The goal is to let handle the logic by a state machine instead of handling multiple variables/check
 * in a complex flow (login flow, game, etc). Sp, in your session, you just store the current state
 * and in the next call, you check the transitions
 *
 * How it works:
 *   * Define states in a '/states' folder by extending the BaseState class.
 *   * Use or add new transitions in a '/transitions' folder by extending the Transition class.
 *   * Once the states and transition are defined, initialize the state machine with the initial state.
 *   * Call step as much as you want. But usually, you'll want to call it once from your controller.
 */
class StateMachine {

    /** @var Logger */
    private $logger;
    /** @var BaseState */
    private $currentState;
    /** @var string  */
    private $statesNamespace;

    /**
     * StateMachine constructor.
     * @param $initialState mixed Can be the state name or the BaseState object.
     * @param string $statesNamespace
     * @throws Exception
     */
    public function __construct($initialState, string $statesNamespace) {
        $this->logger = new Logger('state-machine');
        $this->statesNamespace = $statesNamespace;

        if (is_string($initialState)) {
            $initialState = StateFactory::createFromName($initialState, $this->statesNamespace);
        }
        if (!is_subclass_of($initialState, BaseState::class)) {
            throw new Exception("The state '" . BaseState::class . "' doesn't inherit from BaseState");
        }

        $this->setState($initialState);
        $this->logger->info("initialize -> " . $initialState::getName());
    }

    /**
     * @param BaseState $state
     */
    public function setState(BaseState $state) {
        $this->currentState = $state;
        $this->logger->info("set new state -> " . $state::getName());
    }

    /**
     * @return BaseState current state of the state machine.
     */
    public function getState() {
        return $this->currentState;
    }

    /**
     * @param StateMachineContext $context
     * @return bool true if there was a successful transition, false otherwise.
     * @throws Exception
     */
    public function step(StateMachineContext $context) {
        $this->logger->info("stepping in " . $this->currentState::getName());
        $newState = $this->currentState->transition($context);
        if ($newState != null) {
            $this->logger->info("transition successful to -> " . $newState);
            $this->setState(StateFactory::createFromName($newState, $this->statesNamespace));
            return true;
        }
        return false;
    }
}
