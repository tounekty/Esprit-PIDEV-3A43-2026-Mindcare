<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAiModerationService
{
    private const GROQ_URL   = 'https://api.groq.com/openai/v1/chat/completions';
    private const GROQ_MODEL = 'llama-3.3-70b-versatile';
    private const MAX_RETRIES = 1;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $groqApiKey,
        private readonly bool $localCommentFilterEnabled = true,
        private readonly bool $localFilterAllowOnApiFailure = false,
    ) {
    }

    /**
     * @return array{
     *     enabled: bool,
     *     checked: bool,
     *     flagged: bool,
     *     categories: string[],
     *     fallbackUsed: bool,
     *     statusCode: int|null,
     *     errorType: string|null,
     *     errorMessage: string|null
     * }
     */
    public function moderate(string $text): array
    {
        $content = trim($text);
        $localModeration = $this->moderateLocally($content);

        if ($content === '') {
            return [
                'enabled'      => true,
                'checked'      => true,
                'flagged'      => false,
                'categories'   => [],
                'fallbackUsed' => false,
                'statusCode'   => null,
                'errorType'    => null,
                'errorMessage' => null,
            ];
        }

        if (!$this->isEnabled()) {
            if ($this->localCommentFilterEnabled) {
                return [
                    'enabled'      => true,
                    'checked'      => true,
                    'flagged'      => $localModeration['flagged'],
                    'categories'   => $localModeration['categories'],
                    'fallbackUsed' => true,
                    'statusCode'   => null,
                    'errorType'    => null,
                    'errorMessage' => null,
                ];
            }

            return [
                'enabled'      => false,
                'checked'      => false,
                'flagged'      => false,
                'categories'   => [],
                'fallbackUsed' => false,
                'statusCode'   => null,
                'errorType'    => 'disabled',
                'errorMessage' => 'Groq API key is missing.',
            ];
        }

        try {
            $attempt    = 0;
            $statusCode = null;
            $data       = [];

            $systemPrompt = 'You are a strict content moderation assistant for a mental-health platform used by students and psychologists. Analyze the user comment and return ONLY valid JSON  no explanation, no markdown, nothing else. JSON format: {"flagged": <true|false>, "categories": [<list of matched categories or empty array>]}. Possible categories: "harassment", "hate", "sexual", "violence", "spam", "self-harm", "bad_words". Flag the comment if it contains: insults, hate speech, explicit content, spam/ads, threats, or severe vulgarity. Be strict but not overly sensitive  constructive criticism and emotional expressions are acceptable.';

            while (true) {
                $response = $this->httpClient->request('POST', self::GROQ_URL, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->groqApiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model'       => self::GROQ_MODEL,
                        'messages'    => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user',   'content' => $content],
                        ],
                        'max_tokens'  => 80,
                        'temperature' => 0,
                    ],
                    'timeout' => 15,
                ]);

                $statusCode = $response->getStatusCode();
                $data       = $response->toArray(false);

                if ($statusCode >= 500 && $attempt < self::MAX_RETRIES) {
                    $attempt++;
                    sleep(1);
                    continue;
                }

                break;
            }

            if ($statusCode >= 400 || !isset($data['choices'][0]['message']['content'])) {
                $errorMessage = (string) ($data['error']['message'] ?? 'Groq HTTP ' . $statusCode);
                $this->logger->warning('Groq moderation returned an unexpected response.', [
                    'status_code' => $statusCode,
                    'response'    => $data,
                ]);

                if ($this->localCommentFilterEnabled && ($this->localFilterAllowOnApiFailure || $localModeration['flagged'])) {
                    return [
                        'enabled'      => true,
                        'checked'      => true,
                        'flagged'      => $localModeration['flagged'],
                        'categories'   => $localModeration['categories'],
                        'fallbackUsed' => true,
                        'statusCode'   => $statusCode,
                        'errorType'    => null,
                        'errorMessage' => null,
                    ];
                }

                return [
                    'enabled'      => true,
                    'checked'      => false,
                    'flagged'      => false,
                    'categories'   => [],
                    'fallbackUsed' => false,
                    'statusCode'   => $statusCode,
                    'errorType'    => $this->resolveErrorType($statusCode),
                    'errorMessage' => $errorMessage,
                ];
            }

            $raw    = trim((string) $data['choices'][0]['message']['content']);
            $raw    = preg_replace('/^```(?:json)?\s*/i', '', $raw) ?? $raw;
            $raw    = preg_replace('/\s*```$/', '', $raw) ?? $raw;
            $parsed = json_decode($raw, true);

            if (!is_array($parsed)) {
                $this->logger->warning('Groq moderation: could not parse JSON response.', ['raw' => $raw]);

                if ($this->localCommentFilterEnabled && ($this->localFilterAllowOnApiFailure || $localModeration['flagged'])) {
                    return [
                        'enabled'      => true,
                        'checked'      => true,
                        'flagged'      => $localModeration['flagged'],
                        'categories'   => $localModeration['categories'],
                        'fallbackUsed' => true,
                        'statusCode'   => 200,
                        'errorType'    => null,
                        'errorMessage' => null,
                    ];
                }

                return [
                    'enabled'      => true,
                    'checked'      => false,
                    'flagged'      => false,
                    'categories'   => [],
                    'fallbackUsed' => false,
                    'statusCode'   => 200,
                    'errorType'    => 'parse_error',
                    'errorMessage' => 'Groq returned non-JSON response.',
                ];
            }

            $flagged           = (bool) ($parsed['flagged'] ?? false);
            $flaggedCategories = array_values(array_filter((array) ($parsed['categories'] ?? []), fn($c) => is_string($c)));

            if ($this->localCommentFilterEnabled && $localModeration['flagged']) {
                $flagged = true;
                $flaggedCategories = array_values(array_unique(array_merge($flaggedCategories, $localModeration['categories'])));
            }

            return [
                'enabled'      => true,
                'checked'      => true,
                'flagged'      => $flagged,
                'categories'   => $flaggedCategories,
                'fallbackUsed' => false,
                'statusCode'   => 200,
                'errorType'    => null,
                'errorMessage' => null,
            ];

        } catch (\Throwable $exception) {
            $this->logger->error('Groq moderation call failed.', ['error' => $exception->getMessage()]);

            if ($this->localCommentFilterEnabled && ($this->localFilterAllowOnApiFailure || $localModeration['flagged'])) {
                return [
                    'enabled'      => true,
                    'checked'      => true,
                    'flagged'      => $localModeration['flagged'],
                    'categories'   => $localModeration['categories'],
                    'fallbackUsed' => true,
                    'statusCode'   => null,
                    'errorType'    => null,
                    'errorMessage' => null,
                ];
            }

            return [
                'enabled'      => true,
                'checked'      => false,
                'flagged'      => false,
                'categories'   => [],
                'fallbackUsed' => false,
                'statusCode'   => null,
                'errorType'    => 'network',
                'errorMessage' => $exception->getMessage(),
            ];
        }
    }

    private function isEnabled(): bool
    {
        return str_starts_with(trim($this->groqApiKey), 'gsk_');
    }

    private function resolveErrorType(?int $statusCode): string
    {
        return match (true) {
            $statusCode === 429             => 'rate_limit',
            $statusCode === 401
            || $statusCode === 403          => 'auth',
            $statusCode !== null
            && $statusCode >= 500           => 'provider',
            default                         => 'api',
        };
    }

    /**
     * @return array{flagged: bool, categories: string[]}
     */
    private function moderateLocally(string $content): array
    {
        if (!$this->localCommentFilterEnabled || $content === '') {
            return [
                'flagged' => false,
                'categories' => [],
            ];
        }

        $normalized = mb_strtolower($content);

        $toxicityPatterns = [
            '/\bnul(le)?\b/u',
            '/\bidiot(e)?\b/u',
            '/\bimb[eé]cile\b/u',
            '/\bconnard(e)?\b/u',
            '/\bmerde\b/u',
            '/va\s+te\s+faire/u',
            '/\bferme\s+ta\s+gueule\b/u',
            '/\bstupide\b/u',
        ];

        $spamPatterns = [
            '/https?:\/\//i',
            '/\b(?:gagner\s+de\s+l\'argent|argent\s+facile|offre\s+incroyable|promo\s+exclusive|bitcoin|casino)\b/ui',
            '/\b(?:cliquez\s+ici|click\s+here|dm\s+me|contact\s+me)\b/ui',
            '/(.)\1{6,}/u',
        ];

        $categories = [];

        foreach ($toxicityPatterns as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                $categories[] = 'harassment';
                $categories[] = 'bad_words';
                break;
            }
        }

        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                $categories[] = 'spam';
                break;
            }
        }

        $categories = array_values(array_unique($categories));

        return [
            'flagged' => $categories !== [],
            'categories' => $categories,
        ];
    }
}