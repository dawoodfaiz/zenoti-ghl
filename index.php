<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\EventDispatcher;
use App\EventRouter;
use App\Plugins\Logger;

try {
    $payloadJSON = file_get_contents('php://input');
    $payload = json_decode($payloadJSON);

    $logger = Logger::getInstance($payload->event_type ?? 'unknown-event');
    $logger->info($payloadJSON);

    if ($payload) {
        $event = (new EventRouter())->route($payload);

        if ($event) {
            (new EventDispatcher())->dispatch($event);
        }
    }
} catch (\Throwable $th) {
    $logger = Logger::getInstance('errors');
    $logger->info($th->getMessage());
}

http_response_code(200);
