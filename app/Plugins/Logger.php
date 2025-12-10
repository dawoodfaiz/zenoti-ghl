<?php

declare(strict_types=1);

namespace App\Plugins;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;

class Logger
{
    private static array $instances = [];

    public static function getInstance(string $logType = 'app'): MonologLogger
    {
        if (!isset(self::$instances[$logType])) {
            $logFolder = __DIR__ . '/../../logs';

            if (!is_dir($logFolder)) {
                mkdir($logFolder, 0777, true);
            }

            $logFile = $logFolder . '/' . $logType . '-' . date('Y-m-d') . '.log';

            $logger = new MonologLogger($logType);
            $handler = new StreamHandler($logFile, Level::Info);
            $formatter = new LineFormatter(null, 'Y-m-d H:i:s', true, true);

            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);

            self::$instances[$logType] = $logger;
        }

        return self::$instances[$logType];
    }
}
