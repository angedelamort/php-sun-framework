<?php
namespace sunframework\system;

use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBarException;

class Stopwatch {
    /** @var TimeDataCollector */
    private static $timeDataCollector = null;

    /**
     * Useful for debugBar
     * @return TimeDataCollector
     */
    public static function getCollector() {
        if (self::$timeDataCollector === null) {
            self::$timeDataCollector = new TimeDataCollector();
        }
        return self::$timeDataCollector;
    }

    public $name;
    private $label;
    private $start;
    private $end;
    private $stackCount = 0;

    public function __construct(string $name = null, string $label = null) {
        $this->name = $name;
        $this->label = $label;
    }


    public function start() {
        $this->start = microtime(true);
        if (self::$timeDataCollector !== null && $this->name !== null) {
            $this->stackCount++;
            if (!self::$timeDataCollector->hasStartedMeasure($this->name)) {
                self::$timeDataCollector->startMeasure($this->name, $this->label);
            }
        }
        return $this;
    }

    /**
     * @throws DebugBarException
     */
    public function stop() {
        if (self::$timeDataCollector !== null && $this->name !== null) {
            $this->stackCount--;
            if (self::$timeDataCollector->hasStartedMeasure($this->name) && $this->stackCount == 0) {
                self::$timeDataCollector->stopMeasure($this->name);
            }
        }
        $this->end = microtime(true);
        return $this;
    }

    public function getElapsed() {
        return $this->end - $this->start;
    }
}