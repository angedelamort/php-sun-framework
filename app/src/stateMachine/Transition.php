<?php

namespace sunframework\stateMachine;


class Transition {

    /** @var BaseState  */
    private $stateTarget;
    /** @var array<callable> */
    private $conditions = [];
    /** @var callable(StateMachineContext sm)  */
    private $onExecute;
    /** @var string */
    private $name;

    public function __construct(string $stateTarget, callable $onExecute = null, $name = null) {
        $this->stateTarget = $stateTarget;
        $this->onExecute = $onExecute;
        $this->name = $name;
    }

    /**
     * @return string the state that the transition will go to.
     */
    public final function getState() {
        return $this->stateTarget;
    }

    /**
     * @param StateMachineContext $context
     * @return bool true if all conditions are satisfied. If no condition, returns true.
     */
    public final function isSatisfied(StateMachineContext $context) {
        foreach ($this->conditions as $condition) {
            if (!call_user_func_array($condition, [$context])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param StateMachineContext $context
     */
    public function execute(StateMachineContext $context) {
        if ($this->onExecute) {
            call_user_func_array($this->onExecute, [$context]);
        }
    }

    /**
     * Add a condition that will be tested in the isSatisfied() method. The
     * StateMachineContext will be passed as parameter.
     * The elements will always be added at the beginning of the array.
     * @param callable $condition (StateMachineContext $context) : bool
     * @return $this
     */
    public final function addCondition(callable $condition) {
        array_unshift($this->conditions, $condition);
        return $this;
    }

    public function toString() {
        $count = count($this->conditions);
        return ($this->name ? $this->name : get_called_class()) . " {conditionCount: $count}";
    }
}