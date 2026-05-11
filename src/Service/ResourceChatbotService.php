<?php

namespace App\Service;

use App\Repository\ResourceRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ResourceChatbotService
{
    private const GROQ_URL   = 'https://api.groq.com/openai/v1/chat/completions';
    private const GROQ_MODEL = 'llama-3.3-70b-versatile';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ResourceRepository  $resourceRepository,
        private readonly LoggerInterface     $logger,
        private readonly string              $groqApiKey,
    ) {
    }

    /**
     * @param array<int, array{role: string, content: string}> $history
     * @return array{reply: string, error: string|null}
     */
    public function chat(string $userMessage, array $history = []): array
    {
        if (trim($userMessage) === '') {
            return ['reply' => '', 'error' => 'Message vide.'];
        }

        if ($this->groqApiKey !== '' && str_starts_with($this->groqApiKey, 'gsk_')) {
            $result = $this->callGroq($userMessage, $history);
            if ($result !== null) {
                return $result;
            }
            $this->logger->warning('ResourceChatbot: Groq unavailable.');
            return ['reply' => '', 'error' => 'Le service IA est temporairement indisponible. Veuillez réessayer dans quelques instants.'];
        }

        return ['reply' => '', 'error' => 'Clé API Groq non configurée. Ajoutez GROQ_API_KEY dans .env.local.'];
    }

    // =========================================================
    // Groq API (OpenAI-compatible)
    // =========================================================

    /** @return array{reply: string, error: string|null}|null */
    private function callGroq(string $userMessage, array $history): ?array
    {
        $resources    = $this->resourceRepository->findBy([], ['createdAt' => 'DESC'], 50);
        $catalogue    = $this->buildCatalogue($resources);
        $systemPrompt = $this->buildSystemPrompt($catalogue);

        // Build messages array (OpenAI format)
        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($history as $msg) {
            $role       = in_array($msg['role'] ?? '', ['assistant', 'user'], true) ? $msg['role'] : 'user';
            $messages[] = ['role' => $role, 'content' => (string) ($msg['content'] ?? '')];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $body = [
            'model'       => self::GROQ_MODEL,
            'messages'    => $messages,
            'max_tokens'  => 450,
            'temperature' => 0.7,
        ];

        try {
            $response   = $this->httpClient->request('POST', self::GROQ_URL, [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                ],
                'json'    => $body,
                'timeout' => 25,
            ]);

            $statusCode = $response->getStatusCode();
            $data       = $response->toArray(false);

            if ($statusCode !== 200) {
                $errMsg = (string) ($data['error']['message'] ?? 'Groq HTTP ' . $statusCode);
                $this->logger->warning('ResourceChatbot Groq error', ['status' => $statusCode, 'msg' => $errMsg]);
                return null;
            }

            $text = (string) ($data['choices'][0]['message']['content'] ?? '');

            if ($text === '') {
                return null;
            }

            return ['reply' => trim($text), 'error' => null];

        } catch (\Throwable $e) {
            $this->logger->error('ResourceChatbot Groq exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function buildSystemPrompt(string $catalogue): string
    {
        return <<<PROMPT
Tu es un assistant virtuel bienveillant intégré à la plateforme MindCare, une application de santé mentale.
Tu aides les utilisateurs à :
1. Trouver des ressources éducatives (articles et vidéos) adaptées à leur situation ou humeur.
2. Répondre à leurs questions sur le contenu des ressources disponibles.
3. Les guider pour laisser un commentaire constructif sur une ressource.
4. Expliquer comment fonctionne la modération des commentaires (filtre automatique IA).

Voici le catalogue actuel des ressources disponibles :
{$catalogue}

Règles importantes :
- Réponds TOUJOURS en français.
- Sois empathique, chaleureux et concis (3-5 phrases maximum).
- Si tu recommandes une ressource, mentionne son titre et écris son ID entre crochets : [ID:3].
- Ne fabrique jamais de ressources absentes du catalogue.
- Si l'utilisateur exprime une détresse sévère, encourage-le à consulter un professionnel de santé mentale.
PROMPT;
    }

    /** @param list<\App\Entity\Resource> $resources */
    private function buildCatalogue(array $resources): string
    {
        $catalogue = '';
        foreach ($resources as $resource) {
            $type       = $resource->getType() === 'video' ? 'Vidéo YouTube' : 'Article';
            $catalogue .= sprintf(
                "- [%s] %s (ID:%d) : %s\n",
                $type,
                (string) $resource->getTitle(),
                (int) $resource->getId(),
                mb_substr((string) $resource->getDescription(), 0, 120)
            );
        }
        return $catalogue ?: "Aucune ressource disponible.\n";
    }

}
