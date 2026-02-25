<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class ZoomApiService
{
    private string $clientId;
    private string $clientSecret;
    private string $accountId;
    private HttpClientInterface $httpClient;
    private ?string $accessToken = null;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $zoomClientId,
        string $zoomClientSecret,
        string $zoomAccountId
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->clientId = $zoomClientId;
        $this->clientSecret = $zoomClientSecret;
        $this->accountId = $zoomAccountId;
    }

    /**
     * Get an access token from Zoom OAuth
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://zoom.us/oauth/token', [
                'timeout' => 5,
                'auth_basic' => [$this->clientId, $this->clientSecret],
                'query' => [
                    'grant_type' => 'account_credentials',
                    'account_id' => $this->accountId,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $body = $response->getContent(false);
                $this->logger->error('Zoom OAuth failed', [
                    'status' => $statusCode,
                    'body' => mb_substr((string) $body, 0, 1000),
                ]);
                throw new \Exception("Zoom OAuth failed with status {$statusCode}");
            }

            $data = $response->toArray();
            $this->accessToken = $data['access_token'] ?? null;

            if (!$this->accessToken) {
                throw new \Exception('Failed to obtain Zoom access token - no token in response');
            }

            return $this->accessToken;
        } catch (ClientExceptionInterface | ServerExceptionInterface $e) {
            $this->logger->error('Zoom OAuth exception', [
                'message' => $e->getMessage(),
            ]);
            throw new \Exception('Zoom OAuth error: ' . $e->getMessage());
        }
    }

    /**
     * Create a Zoom meeting for an appointment
     * NOTE: In production, ensure your Zoom credentials are valid
     */
    public function createMeeting(
        string $userId,
        string $topic,
        \DateTimeInterface $startTime,
        int $durationMinutes = 60,
        ?string $description = null
    ): array {
        // Check if credentials are configured
        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->accountId)) {
            throw new \Exception('Zoom credentials are missing');
        }
        
        try {
            $token = $this->getAccessToken();

            $payload = [
                'topic' => $topic,
                'type' => 2, // Scheduled meeting
                'start_time' => $startTime->format('Y-m-d\TH:i:s'),
                'duration' => $durationMinutes,
                'timezone' => 'UTC',
                'settings' => [
                    'host_video' => true,
                    'participant_video' => true,
                    'join_before_host' => false,
                    'mute_upon_entry' => false,
                    'waiting_room' => false,
                    'meeting_authentication' => false,
                ],
            ];

            if (!empty($description)) {
                $payload['agenda'] = $description;
            }

            $response = $this->httpClient->request('POST', "https://api.zoom.us/v2/users/{$userId}/meetings", [
                'timeout' => 10,
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 201) {
                $body = $response->getContent(false);
                $this->logger->error('Zoom meeting creation failed', [
                    'status' => $statusCode,
                    'body' => mb_substr((string) $body, 0, 1000),
                ]);
                throw new \Exception("Failed to create Zoom meeting: HTTP {$statusCode}");
            }

            $data = $response->toArray();

            return [
                'id' => $data['id'] ?? null,
                'uuid' => $data['uuid'] ?? null,
                'join_url' => $data['join_url'] ?? null,
                'start_time' => $data['start_time'] ?? null,
                'topic' => $data['topic'] ?? null,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Zoom meeting exception', [
                'message' => $e->getMessage(),
            ]);
            throw new \Exception('Error creating Zoom meeting: ' . $e->getMessage());
        }
    }

    /**
     * Delete a Zoom meeting
     */
    public function deleteMeeting(int $meetingId): bool
    {
        try {
            $token = $this->getAccessToken();

            $response = $this->httpClient->request('DELETE', "https://api.zoom.us/v2/meetings/{$meetingId}", [
                'timeout' => 5,
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
            ]);

            return $response->getStatusCode() === 204;
        } catch (ClientExceptionInterface | ServerExceptionInterface | \Exception $e) {
            throw new \Exception('Error deleting Zoom meeting: ' . $e->getMessage());
        }
    }

    /**
     * Get Zoom user ID by email
     */
    public function getUserIdByEmail(string $email): ?int
    {
        try {
            $token = $this->getAccessToken();

            $response = $this->httpClient->request('GET', 'https://api.zoom.us/v2/users', [
                'timeout' => 5,
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'query' => [
                    'search_key' => 'email',
                    'search_value' => $email,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();
            $users = $data['users'] ?? [];

            if (!empty($users)) {
                return $users[0]['id'] ?? null;
            }

            return null;
        } catch (ClientExceptionInterface | ServerExceptionInterface | \Exception $e) {
            throw new \Exception('Error fetching Zoom user: ' . $e->getMessage());
        }
    }
}
