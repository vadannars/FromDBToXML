<?php

namespace App;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    public static function createLogger(Config $config, string $name = 'app'): LoggerInterface
    {
        $logDestination = $config->getLogDestination();

        $levelMap = [
            'debug'     => Level::Debug,
            'info'      => Level::Info,
            'notice'    => Level::Notice,
            'warning'   => Level::Warning,
            'error'     => Level::Error,
            'critical'  => Level::Critical,
            'alert'     => Level::Alert,
            'emergency' => Level::Emergency
        ];

        $configLevel = $config->getLogLevel();
        $level = $levelMap[$configLevel] ?? Level::Debug;

        $handler = new StreamHandler($logDestination, $level);
        $handler->setFormatter(new LineFormatter(null, null, true, true));
        
        $logger = new Logger($name);
        $logger->pushHandler($handler);

        return $logger;
    }
}
