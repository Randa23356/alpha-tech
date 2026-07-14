<?php
// src/config/google.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/config.php';

class GoogleAuth {
    private $client;
    
    public function __construct() {
        $this->client = new Google_Client();
        $this->client->setClientId('729994754873-m7qbjk8jafgqvrvpmpspjnqpqaeuirj1.apps.googleusercontent.com');
        $this->client->setClientSecret('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');
        $this->client->setRedirectUri(BASE_URL . '/google-callback');
        $this->client->addScope('email');
        $this->client->addScope('profile');
    }
    
    public function getAuthUrl() {
        return $this->client->createAuthUrl();
    }
    
    public function getClient() {
        return $this->client;
    }
}