<?php

namespace sunframework\stateMachine;

use sunframework\system\SunLogger;

abstract class BaseState {

    /** @var array Transition */
    private $transitions = [];
    private $logger;

    public function __construct() {
        $this->logger = new SunLogger('state-machine');
    }

    /**
     * By default, it will use the name of the class.
     * @return string Unique name for the state. Will be used to instantiate from the saved session.
     */
    public function getName() {
        return self::name();
    }

    public function toString() {
        $count = count($this->transitions);
        return $this->getName() . " {transitionCount: $count}";
    }

    public static function name() {
        $name = get_called_class();
        $index = strrpos(get_called_class(), '\\');
        return $index === -1 ? $name : substr($name, $index + 1);
    }

    /**
     * By default, it will returns the name of "the super call".twig
     * @return string Get the template relative path from "/src/templates" folder.
     * @example return 'game/board.twig';
     */
    public function getView() {
        return self::name() . '.twig';
    }

    /**
     * Will be called when will render the twig template.
     * This will be merged with existing variables such as 'game'
     * @param StateMachineContext $context
     * @return array 'string => value'
     */
    public function getModel(StateMachineContext $context) {
        return [];
    }

    /**
     * Execute the transition if the conditions are satisfied
     * @param StateMachineContext $context
     * @return string The state name if the transition occurred, false otherwise.
     */
    public function transition(StateMachineContext $context) {
        /** @var $transition Transition */
        foreach ($this->transitions as $transition) {
            $this->logger->info(" - testing transition -> " . $transition->toString());
            if ($transition->isSatisfied($context)) {
                $this->logger->info("   - transition satisfied!");
                $transition->execute($context);
                return $transition->getState();
            }
        }

        return null;
    }

    /**
     * Only the state should be able to add its own transitions.
     * They will be executed in the order they were added.
     * @param Transition $transition
     */
    protected function addTransition(Transition $transition) {
        $this->transitions[] = $transition;
    }
}