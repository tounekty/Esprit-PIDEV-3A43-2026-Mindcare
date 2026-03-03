<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HuggingFaceService
{
    private string $apiToken;
    private string $apiUrl;
    private HttpClientInterface $httpClient;
    private string $model = 'mistralai/Mistral-7B-Instruct-v0.2';

    public function __construct(
        HttpClientInterface $httpClient,
        string $huggingFaceApiToken,
        string $huggingFaceApiUrl = 'https://router.huggingface.co/v1/chat/completions'
    ) {
        $this->httpClient = $httpClient;
        $this->apiToken = $huggingFaceApiToken;
        $this->apiUrl = $huggingFaceApiUrl;
    }

    /**
     * Send a prompt to the Mistral model and get a response
     */
    public function chat(string $systemContext, string $userMessage): string
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemContext,
                        ],
                        [
                            'role' => 'user',
                            'content' => $userMessage,
                        ],
                    ],
                    'max_tokens' => 512,
                    'temperature' => 0.7,
                    'top_p' => 0.95,
                ],
                'timeout' => 60,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode === 503) {
                return "Le modèle est en cours de chargement, veuillez réessayer dans quelques secondes.";
            }

            if ($statusCode !== 200) {
                $error = $data['error'] ?? ($data['message'] ?? 'Erreur inconnue');
                return "Erreur API: {$error}";
            }

            // OpenAI-compatible response format
            if (isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }

            return "Désolé, je n'ai pas pu générer une réponse.";
        } catch (\Exception $e) {
            return "Erreur de connexion: " . $e->getMessage();
        }
    }
}
