<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Illuminate\Support\Facades\Log;

class GoogleService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName(config('google.application_name'));
        $this->client->setClientId(config('google.client_id'));
        $this->client->setClientSecret(config('google.client_secret'));
        $this->client->setRedirectUri(config('google.redirect_uri'));
        $this->client->setScopes(config('google.scopes'));
        $this->client->setAccessType(config('google.access_type'));
        $this->client->setApprovalPrompt(config('google.approval_prompt'));
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function authenticate($code)
    {
        return $this->client->fetchAccessTokenWithAuthCode($code);
    }

    public function setAccessToken($token)
    {
        $this->client->setAccessToken($token);
    }

    public function listMessages()
    {
        $service = new Gmail($this->client);
        $messages = $service->users_messages->listUsersMessages('me');

        foreach ($messages->getMessages() as $message)
            Log::channel('stderr')->info($message->getPayload());

        dd();
        return $service->users_messages->listUsersMessages('me');
    }
}
