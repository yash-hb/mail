<?php

namespace App\Services;

use Google_Client;
use Google_Service_Gmail;

class GmailService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('fir-e689e-37817e27282c.json'));
        $this->client->setRedirectUri(url('/callback'));
        $this->client->addScope(Google_Service_Gmail::GMAIL_READONLY);
        $this->client->setAccessType('offline');
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function fetchEmails($token)
    {
        $this->client->setAccessToken($token);
        $service = new Google_Service_Gmail($this->client);

        $messages = $service->users_messages->listUsersMessages('me', ['maxResults' => 10]);
        $emails = [];

        foreach ($messages->getMessages() as $message) {
            $msg = $service->users_messages->get('me', $message->getId());
            $emails[] = [
                'id' => $msg->getId(),
                'snippet' => $msg->getSnippet()
            ];
        }

        return $emails;
    }
}
