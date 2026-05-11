<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WellBeingApiService
{
    public function __construct(private HttpClientInterface $client) {}

    public function getMotivation(): string
    {
        $response = $this->client->request(
            'GET',
            'https://zenquotes.io/api/random'
        );

        $data = $response->toArray();

        return $data[0]['q'] ?? 'Prenez soin de vous 🤍';
    }
}
