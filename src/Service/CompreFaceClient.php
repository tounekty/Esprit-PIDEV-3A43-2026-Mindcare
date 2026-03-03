<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CompreFaceClient
{
    private HttpClientInterface $httpClient;
    private string $baseUrl;
    private string $apiKey;
    private float $minSimilarity;

    public function __construct(
        HttpClientInterface $httpClient,
        string $baseUrl,
        string $apiKey,
        float $minSimilarity
    ) {
        $this->httpClient = $httpClient;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->minSimilarity = $minSimilarity;
    }

    public function addFaceExample(string $subject, string $base64Image): array
    {
        $path = '/api/v1/recognition/faces?subject=' . rawurlencode($subject);

        return $this->requestMultipart('POST', $path, $base64Image);
    }

    public function deleteFacesBySubject(string $subject): void
    {
        $path = '/api/v1/recognition/faces?subject=' . rawurlencode($subject);

        $this->requestJson('DELETE', $path);
    }

    public function recognize(string $base64Image): array
    {
        $path = '/api/v1/recognition/recognize?prediction_count=1&det_prob_threshold=0.7';

        return $this->requestMultipart('POST', $path, $base64Image);
    }

    public function extractBestMatch(array $response): ?array
    {
        $firstResult = $response['result'][0] ?? null;
        $firstSubject = $firstResult['subjects'][0] ?? null;

        if (!$firstSubject || !isset($firstSubject['subject'], $firstSubject['similarity'])) {
            return null;
        }

        return [
            'subject' => (string) $firstSubject['subject'],
            'similarity' => (float) $firstSubject['similarity'],
        ];
    }

    public function getMinSimilarity(): float
    {
        return $this->minSimilarity;
    }

    private function requestMultipart(string $method, string $path, string $base64Image): array
    {
        $binaryImage = base64_decode($base64Image, true);
        if ($binaryImage === false) {
            throw new \RuntimeException('Invalid base64 image data.');
        }

        $boundary = '----FormBoundary' . bin2hex(random_bytes(8));
        $body = "--{$boundary}\r\n"
            . "Content-Disposition: form-data; name=\"file\"; filename=\"face.jpg\"\r\n"
            . "Content-Type: image/jpeg\r\n\r\n"
            . $binaryImage . "\r\n"
            . "--{$boundary}--\r\n";

        $options = [
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
                'x-api-key' => $this->apiKey,
            ],
            'body' => $body,
        ];

        $response = $this->httpClient->request($method, $this->baseUrl . $path, $options);
        $status = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($status >= 400) {
            $message = $data['message'] ?? 'CompreFace request failed.';
            throw new \RuntimeException($message);
        }

        return $data;
    }

    private function requestJson(string $method, string $path, array $options = []): array
    {
        $options = array_merge([
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->apiKey,
            ],
        ], $options);

        $response = $this->httpClient->request($method, $this->baseUrl . $path, $options);
        $status = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($status >= 400) {
            $message = $data['message'] ?? 'CompreFace request failed.';
            throw new \RuntimeException($message);
        }

        return $data;
    }
}
