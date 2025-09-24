<?php

namespace App;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    /**
     * Skapar och konfigurerar en logger.
     *
     * @param Config $config Konfigurationsobjektet för att hämta loggnivå och destination.
     * @param string $name Namn för loggaren.
     * @return LoggerInterface Den konfigurerade loggerinstansen.
     */
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

        try {
            $handler = new StreamHandler($logDestination, $level);
        } catch (\Exception $e) {
            error_log(
                "Kunde inte skriva till loggfil: " . $e->getMessage() .
                " - loggar till stderr istället."
            );
            $handler = new StreamHandler('php://stderr', $level);
        }

        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $logger = new Logger($name);
        $logger->pushHandler($handler);

        return $logger;
    }
}
