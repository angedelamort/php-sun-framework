<?php

namespace sunframework\stateMachine;

use Slim\Http\Request;


class StateMachineContext {

    /** @var array  */
    private $args;
    /** @var Request  */
    private $request;
    /** @var mixed|null  */
    private $context;

    /**
     * StateMachineContext constructor.
     * @param $context mixed Can be anything you want to use in your state or transitions.
     * @param Request $request Client request
     * @param array $args
     */
    public function __construct(Request $request, array $args, $context = null) {
        $this->context = $context;
        $this->request = $request;
        $this->args = $args;
    }

    public function getContext() { return $this->context; }
    public function getRequest() { return $this->request; }
    public function getArgs() { return $this->args; }
}