<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Interfaces\EventInterface;
use App\Interfaces\ListenerInterface;
use App\Plugins\Logger;
use App\Services\ZenotiService;

class GenerateZenotiAccessTokenListener implements ListenerInterface
{
    public function handle(EventInterface $event): void
    {
        $eventContext = $event->getEventContext();
        $zenotiService = ZenotiService::getInstance();

        $accessTokenPostData = [];
        $accessTokenPostData = [
            'account_name' => $zenotiService->getAccountName(),
            'user_name' => $zenotiService->getUserName(),
            'password' => $zenotiService->getPassword(),
            'grant_type' => $zenotiService->getGrantType(),
            'app_id' => $zenotiService->getAppID(),
            'app_secret' => $zenotiService->getAppSecret(),
        ];

        $accessTokenResponseJSON = $zenotiService->generateAccessToken($accessTokenPostData);
        $accessTokenResponse = json_decode($accessTokenResponseJSON);

        if (!isset($accessTokenResponse->credentials->access_token)) {
            (Logger::getInstance($eventContext->eventType))->error('Zenoti access token error: ' . $accessTokenResponseJSON);
            http_response_code(200);
            exit;
        }

        $accessToken = $accessTokenResponse->credentials->access_token;

        $eventContext->accessTokenZenoti = $accessToken;
    }
}
