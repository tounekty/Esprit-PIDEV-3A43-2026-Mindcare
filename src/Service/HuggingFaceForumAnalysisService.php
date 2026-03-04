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
            throw new \RuntimeException('Le texte à analyser est vide.');
        }

        if (trim($this->apiToken) === '') {
            throw new \RuntimeException('HUGGINGFACE_API_TOKEN est manquant.');
        }

        $candidateLabels = [
            'positif',
            'neutre',
            'négatif',
            'urgent',
            'non urgent',
            'détresse psychologique',
        ];

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
            throw new \RuntimeException($errorMessage);
        }

        if (!is_array($payload) || !isset($payload['labels'], $payload['scores']) || !is_array($payload['labels']) || !is_array($payload['scores'])) {
            throw new \RuntimeException('Réponse Hugging Face invalide.');
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

   
}
