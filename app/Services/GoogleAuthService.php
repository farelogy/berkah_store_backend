<?php

namespace App\Services;

use Exception;
use Google_Client;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Log;

class GoogleAuthService
{
    protected ?string $clientId;

    public function __construct()
    {
        $this->clientId = config('services.google.client_id', env('GOOGLE_CLIENT_ID'));
    }

    /**
     * Verifikasi ID Token Google dari aplikasi Flutter.
     * Mengembalikan array data profil [google_id, email, name, avatar] atau null jika tidak valid.
     */
    public function verifyIdToken(string $idToken): ?array
    {
        // 1. Coba verifikasi via Google_Client library jika terpasang
        if (class_exists(Google_Client::class)) {
            try {
                $client = new Google_Client(['client_id' => $this->clientId]);
                $payload = $client->verifyIdToken($idToken);
                if ($payload) {
                    return [
                        'google_id' => $payload['sub'],
                        'email' => $payload['email'],
                        'name' => $payload['name'] ?? explode('@', $payload['email'])[0],
                        'avatar' => $payload['picture'] ?? null,
                    ];
                }
            } catch (Exception $e) {
                Log::warning('Google_Client verifyIdToken failed, fallback to HTTP tokeninfo: '.$e->getMessage());
            }
        }

        // 2. Fallback: Verifikasi via Google OAuth2 tokeninfo HTTP endpoint
        try {
            $http = new HttpClient(['timeout' => 5]);
            $response = $http->get('https://oauth2.googleapis.com/tokeninfo', [
                'query' => ['id_token' => $idToken],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);

                if (isset($data['sub']) && isset($data['email'])) {
                    return [
                        'google_id' => $data['sub'],
                        'email' => $data['email'],
                        'name' => $data['name'] ?? explode('@', $data['email'])[0],
                        'avatar' => $data['picture'] ?? null,
                    ];
                }
            }
        } catch (Exception $e) {
            Log::error('Google OAuth2 HTTP verification failed: '.$e->getMessage());
        }

        return null;
    }
}
