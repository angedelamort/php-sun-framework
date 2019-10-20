<?php

namespace sunframework\stateMachine;


use Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use InvalidArgumentException;

/**
 * Class StateFactory
 * @package sunframework\stateMachine
 * Simple factory class in order to create the appropriate state between sessions using the name.
 */
class StateFactory {

    private static $states = null;

    /**
     * @param string $name
     * @param string $statesNamespace The namespace where your states are.
     * @return BaseState
     * @throws Exception
     */
    public static function createFromName(string $name, string $statesNamespace) {
        StateFactory::initAllStates($statesNamespace);

        if (!isset(StateFactory::$states[$name]))
            throw new InvalidArgumentException("StateFactory -> state '$name' not found.");

        $klass = StateFactory::$states[$name];
        return new $klass();
    }

    /**
     * @param string $statesNamespace
     * @throws Exception
     */
    private static function initAllStates(string $statesNamespace) {
        if (StateFactory::$states != null)
            return;

        $classes = ClassFinder::getClassesInNamespace($statesNamespace, ClassFinder::RECURSIVE_MODE);
        /** @var BaseState $klass */
        foreach ($classes as $klass) {
            StateFactory::$states[$klass::name()] = $klass;
        }
    }
}