<?php
namespace App;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class LoggerFactory
{
    public static function createLogger(Config $config, string $name = 'app'): Logger
    {
        $logPath = __DIR__ . '/../logs/app.log';

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


        $handler = new RotatingFileHandler($logPath, 7, $level);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $logger = new Logger($name);
        $logger->pushHandler($handler);

        return $logger;
    }
}
