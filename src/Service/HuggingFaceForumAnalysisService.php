<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HuggingFaceForumAnalysisService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiToken,
        private readonly string $modelName,
        private readonly float $urgencyThreshold,
        private readonly float $urgencyMargin,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{
     *     sentiment_label: string,
     *     sentiment_score: float,
     *     urgency_label: string,
     *     urgency_score: float,
     *     is_urgent: bool,
     *     model_name: string,
     *     raw_response: string
     * }
     */
    public function analyzeText(string $text): array
    {
        $cleanText = trim($text);
        if ($cleanText === '') {
            return $this->analyzeTextLocally($cleanText, 'empty_text');
        }

        if (trim($this->apiToken) === '') {
            return $this->analyzeTextLocally($cleanText, 'missing_api_token');
        }

        $candidateLabels = [
            'positif',
            'neutre',
            'négatif',
            'urgent',
            'non urgent',
            'détresse psychologique',
        ];

        try {
            $response = $this->httpClient->request('POST', sprintf('https://router.huggingface.co/hf-inference/models/%s', $this->modelName), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $cleanText,
                    'parameters' => [
                        'candidate_labels' => $candidateLabels,
                        'multi_label' => true,
                    ],
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $payload = $response->toArray(false);

            if ($statusCode >= 400) {
                $errorMessage = is_array($payload) ? (string) ($payload['error'] ?? 'Erreur Hugging Face.') : 'Erreur Hugging Face.';
                $this->logger->warning('Forum AI HTTP error, using local fallback.', [
                    'status' => $statusCode,
                    'error' => $errorMessage,
                    'model' => $this->modelName,
                ]);

                return $this->analyzeTextLocally($cleanText, 'http_error');
            }

            if (!is_array($payload) || !isset($payload['labels'], $payload['scores']) || !is_array($payload['labels']) || !is_array($payload['scores'])) {
                $this->logger->warning('Forum AI invalid payload, using local fallback.', [
                    'model' => $this->modelName,
                    'payload_type' => get_debug_type($payload),
                ]);

                return $this->analyzeTextLocally($cleanText, 'invalid_payload');
            }
        } catch (\Throwable $exception) {
            $this->logger->warning('Forum AI request failed, using local fallback.', [
                'error' => $exception->getMessage(),
                'model' => $this->modelName,
            ]);

            return $this->analyzeTextLocally($cleanText, 'request_exception');
        }

        $scoreByLabel = [];
        foreach ($payload['labels'] as $index => $label) {
            if (!is_string($label) || !isset($payload['scores'][$index])) {
                continue;
            }

            $score = (float) $payload['scores'][$index];
            $scoreByLabel[mb_strtolower(trim($label))] = $score;
        }

        $sentimentCandidates = [
            'positif' => $scoreByLabel['positif'] ?? 0.0,
            'neutre' => $scoreByLabel['neutre'] ?? 0.0,
            'négatif' => $scoreByLabel['négatif'] ?? 0.0,
            'negatif' => $scoreByLabel['negatif'] ?? 0.0,
        ];

        if (($sentimentCandidates['negatif'] ?? 0.0) > ($sentimentCandidates['négatif'] ?? 0.0)) {
            $sentimentCandidates['négatif'] = $sentimentCandidates['negatif'];
        }
        unset($sentimentCandidates['negatif']);

        arsort($sentimentCandidates);
        $sentimentLabel = (string) array_key_first($sentimentCandidates);
        $sentimentScore = (float) ($sentimentCandidates[$sentimentLabel] ?? 0.0);

        $urgentScore = (float) ($scoreByLabel['urgent'] ?? 0.0);
        $nonUrgentScore = (float) ($scoreByLabel['non urgent'] ?? 0.0);
        $distressScore = (float) ($scoreByLabel['détresse psychologique'] ?? 0.0);
        $riskSignalScore = max($urgentScore, $distressScore);
        $urgencyDelta = $riskSignalScore - $nonUrgentScore;
        $isUrgent = $riskSignalScore >= $this->urgencyThreshold
            && $urgencyDelta >= $this->urgencyMargin
            && !($sentimentLabel === 'positif' && $sentimentScore >= 0.95 && $riskSignalScore < 0.98);

        $this->logger->info('Forum AI calibration', [
            'model' => $this->modelName,
            'sentiment_label' => $sentimentLabel,
            'sentiment_score' => round($sentimentScore, 4),
            'urgent_score' => round($urgentScore, 4),
            'distress_score' => round($distressScore, 4),
            'non_urgent_score' => round($nonUrgentScore, 4),
            'risk_signal_score' => round($riskSignalScore, 4),
            'urgency_delta' => round($urgencyDelta, 4),
            'urgency_threshold' => $this->urgencyThreshold,
            'urgency_margin' => $this->urgencyMargin,
            'is_urgent' => $isUrgent,
        ]);

        return [
            'sentiment_label' => $sentimentLabel,
            'sentiment_score' => $sentimentScore,
            'urgency_label' => $isUrgent ? 'urgent' : 'non_urgent',
            'urgency_score' => $riskSignalScore,
            'is_urgent' => $isUrgent,
            'model_name' => $this->modelName,
            'raw_response' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
        ];
    }

    private function analyzeTextLocally(string $text, string $reason): array
    {
        $normalized = mb_strtolower($text);

        $positiveKeywords = [
            'bien', 'mieux', 'calme', 'heureux', 'motivé', 'confiant', 'satisfait', 'soulagé', 'positif', 'content',
            'good', 'better', 'calm', 'happy', 'motivated', 'confident', 'satisfied',
        ];

        $negativeKeywords = [
            'mal', 'triste', 'anxieux', 'anxieuse', 'stress', 'stressé', 'déçu', 'déprimé', 'épuisé', 'fatigué',
            'angoisse', 'insomnie', 'peur', 'découragé', 'perdu', 'suicid',
            'bad', 'anxious', 'stressed', 'depressed', 'exhausted', 'tired',
        ];

        $urgentKeywords = [
            'urgence', 'urgent', 'danger', 'crise', 'suicid', 'me faire du mal', 'envie de mourir', 'help', 'aide',
        ];

        $positiveHits = $this->countKeywordHits($normalized, $positiveKeywords);
        $negativeHits = $this->countKeywordHits($normalized, $negativeKeywords);
        $urgentHits = $this->countKeywordHits($normalized, $urgentKeywords);

        $sentimentLabel = 'neutre';
        $sentimentScore = 0.55;

        if ($negativeHits > $positiveHits) {
            $sentimentLabel = 'négatif';
            $sentimentScore = min(0.99, 0.6 + (0.08 * ($negativeHits - $positiveHits)));
        } elseif ($positiveHits > $negativeHits) {
            $sentimentLabel = 'positif';
            $sentimentScore = min(0.99, 0.6 + (0.08 * ($positiveHits - $negativeHits)));
        }

        $riskSignalScore = min(1.0, 0.35 + (0.2 * $urgentHits) + (0.08 * $negativeHits));
        $nonUrgentScore = max(0.0, 1.0 - $riskSignalScore);
        $urgencyDelta = $riskSignalScore - $nonUrgentScore;
        $isUrgent = $riskSignalScore >= $this->urgencyThreshold
            && $urgencyDelta >= $this->urgencyMargin
            && !($sentimentLabel === 'positif' && $sentimentScore >= 0.95 && $riskSignalScore < 0.98);

        $payload = [
            'source' => 'local_fallback',
            'reason' => $reason,
            'positive_hits' => $positiveHits,
            'negative_hits' => $negativeHits,
            'urgent_hits' => $urgentHits,
            'urgency_threshold' => $this->urgencyThreshold,
            'urgency_margin' => $this->urgencyMargin,
        ];

        return [
            'sentiment_label' => $sentimentLabel,
            'sentiment_score' => $sentimentScore,
            'urgency_label' => $isUrgent ? 'urgent' : 'non_urgent',
            'urgency_score' => $riskSignalScore,
            'is_urgent' => $isUrgent,
            'model_name' => $this->modelName . ' (fallback)',
            'raw_response' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
        ];
    }

    private function countKeywordHits(string $text, array $keywords): int
    {
        $hits = 0;
        foreach ($keywords as $keyword) {
            if (mb_strpos($text, $keyword) !== false) {
                ++$hits;
            }
        }

        return $hits;
    }
}
