<?php
namespace sunframework\system;

use DebugBar\Bridge\MonologCollector;
use DebugBar\DebugBar;
use DebugBar\DebugBarException;
use Monolog\Logger;

/**
 * Class SunLogger
 * @package sunframework\system
 * A simple class that encapsulate Monolog and the debug bar.
 * It will be initialized at the beginning of the constructor after
 * create a new instance of SunApp. You can still use manually each of them.
 */
class SunLogger extends Logger {

    /** @var DebugBar */
    private static $debugBar = null;

    public static function init(DebugBar $debugBar) {
        self::$debugBar = $debugBar;
    }

    /**
     * Print a message directly in the message section in the debug bar.
     * @param string $message
     */
    public function message(string $message) {
        if (self::$debugBar) {
            self::$debugBar["messages"]->addMessage($message);
        } else {
            $this->info($message);
        }
    }

    /**
     * SunLogger constructor.
     * @param $name
     * @param array $handlers
     * @param array $processors
     * @throws DebugBarException
     */
    public function __construct($name, array $handlers = array(), array $processors = array()) {
        parent::__construct($name, $handlers, $processors);

        if (self::$debugBar) {
            // stupid because no constant for name... it's in the default params
            if (self::$debugBar->hasCollector('monolog')) {
                /** @var MonologCollector $collector */
                $collector = self::$debugBar->getCollector('monolog');
                $collector->addLogger($this);
            } else {
                self::$debugBar->addCollector(new MonologCollector($this));
            }
        }
    }
}