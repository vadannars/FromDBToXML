<?php
namespace App;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class LoggerFactory
{
    public static function createLogger(Config $config, string $name = 'app'): Logger
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

        if (str_starts_with($logDestination, 'php://')) {
            $handler = new StreamHandler($logDestination, $level);
        } else {
            $handler = new RotatingFileHandler($logDestination, 7, $level);
        }
        
        $handler->setFormatter(new LineFormatter(null, null, true, true));
        $logger = new Logger($name);
        $logger->pushHandler($handler);

        return $logger;
    }
}
