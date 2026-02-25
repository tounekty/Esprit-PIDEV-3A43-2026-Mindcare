<?php

namespace App\Service;

use App\Entity\Resource;
use App\Repository\ResourceRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleAnalyticsResourceMetricsService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly ResourceRepository $resourceRepository,
        private readonly string $ga4PropertyId,
        private readonly string $ga4CredentialsPath,
        private readonly int $ga4LookbackDays,
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return array{
     *     enabled: bool,
     *     available: bool,
     *     error: string|null,
     *     periodDays: int,
     *     overview: array{
     *         totalViews: int,
     *         avgEngagementSeconds: float,
     *         bounceRate: float
     *     },
     *     topResources: array<int, array{
     *         resourceId: int,
     *         title: string,
     *         type: string,
     *         pagePath: string,
     *         views: int,
     *         avgEngagementSeconds: float,
     *         bounceRate: float
     *     }>,
     *     topCategories: array<int, array{
     *         label: string,
     *         views: int
     *     }>
     * }
     */
    public function getResourceMetrics(): array
    {
        $periodDays = $this->resolvePeriodDays();
        $baseResult = [
            'enabled' => false,
            'available' => false,
            'error' => null,
            'periodDays' => $periodDays,
            'overview' => [
                'totalViews' => 0,
                'avgEngagementSeconds' => 0.0,
                'bounceRate' => 0.0,
            ],
            'topResources' => [],
            'topCategories' => [],
        ];

        if ('' === trim($this->ga4PropertyId) || '' === trim($this->ga4CredentialsPath)) {
            $baseResult['error'] = 'Google Analytics non configure: GA4_PROPERTY_ID et GA4_CREDENTIALS_PATH sont requis.';

            return $baseResult;
        }

        $credentialsPath = $this->resolveCredentialsPath($this->ga4CredentialsPath);
        if (!is_file($credentialsPath)) {
            $baseResult['error'] = sprintf('Fichier credentials Google introuvable: %s', $credentialsPath);

            return $baseResult;
        }

        $credentialsRaw = @file_get_contents($credentialsPath);
        if (false === $credentialsRaw) {
            $baseResult['error'] = 'Impossible de lire le fichier credentials Google.';

            return $baseResult;
        }

        $credentials = json_decode($credentialsRaw, true);
        if (!is_array($credentials)) {
            $baseResult['error'] = 'Credentials Google invalides (JSON invalide).';

            return $baseResult;
        }

        try {
            $accessToken = $this->fetchAccessToken($credentials);
            $rows = $this->fetchGaRows($accessToken, $periodDays);
            $aggregated = $this->aggregateByResource($rows);
            $final = $this->buildResult($aggregated, $baseResult);
            $final['enabled'] = true;
            $final['available'] = true;

            return $final;
        } catch (\Throwable $exception) {
            $this->logger->warning('Google Analytics resource metrics failed.', [
                'error' => $exception->getMessage(),
            ]);

            $baseResult['enabled'] = true;
            $baseResult['error'] = $exception->getMessage();

            return $baseResult;
        }
    }

    /**
     * @param array<string, mixed> $credentials
     */
    private function fetchAccessToken(array $credentials): string
    {
        $clientEmail = (string) ($credentials['client_email'] ?? '');
        $privateKey = (string) ($credentials['private_key'] ?? '');
        $tokenUri = (string) ($credentials['token_uri'] ?? self::TOKEN_URL);

        if ('' === $clientEmail || '' === $privateKey) {
            throw new \RuntimeException('Credentials Google incomplets: client_email/private_key manquants.');
        }

        $jwt = $this->buildJwt($clientEmail, $privateKey, $tokenUri);
        $response = $this->httpClient->request('POST', $tokenUri, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ],
            'timeout' => 20,
        ]);

        $statusCode = $response->getStatusCode();
        $data = $response->toArray(false);
        if ($statusCode >= 400 || !isset($data['access_token']) || !is_string($data['access_token'])) {
            $message = $this->extractHttpErrorMessage($data) ?? 'Token OAuth Google invalide.';
            throw new \RuntimeException(sprintf('OAuth Google failed (%d): %s', $statusCode, $message));
        }

        return $data['access_token'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchGaRows(string $accessToken, int $periodDays): array
    {
        $property = trim($this->ga4PropertyId);
        $uri = sprintf('https://analyticsdata.googleapis.com/v1beta/properties/%s:runReport', rawurlencode($property));

        $payload = [
            'dateRanges' => [[
                'startDate' => sprintf('%ddaysAgo', $periodDays),
                'endDate' => 'today',
            ]],
            'dimensions' => [
                ['name' => 'pagePath'],
            ],
            'metrics' => [
                ['name' => 'screenPageViews'],
                ['name' => 'userEngagementDuration'],
                ['name' => 'bounceRate'],
            ],
            'dimensionFilter' => [
                'filter' => [
                    'fieldName' => 'pagePath',
                    'stringFilter' => [
                        'matchType' => 'CONTAINS',
                        'value' => '/resources/',
                    ],
                ],
            ],
            'orderBys' => [
                [
                    'metric' => ['metricName' => 'screenPageViews'],
                    'desc' => true,
                ],
            ],
            'limit' => 250,
        ];

        $response = $this->httpClient->request('POST', $uri, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => 25,
        ]);

        $statusCode = $response->getStatusCode();
        $data = $response->toArray(false);
        if ($statusCode >= 400 || !is_array($data)) {
            $message = $this->extractHttpErrorMessage($data) ?? 'runReport failed.';
            throw new \RuntimeException(sprintf('Google Analytics runReport failed (%d): %s', $statusCode, $message));
        }

        $rows = $data['rows'] ?? [];

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{
     *     pagePath: string,
     *     views: int,
     *     engagementSeconds: float,
     *     bounceWeighted: float
     * }>
     */
    private function aggregateByResource(array $rows): array
    {
        $resourceData = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $pagePath = (string) ($row['dimensionValues'][0]['value'] ?? '');
            if (!preg_match('~\/resources\/(\d+)(?:\/|$|\?)~', $pagePath, $matches)) {
                continue;
            }

            $resourceId = (int) ($matches[1] ?? 0);
            if ($resourceId <= 0) {
                continue;
            }

            $metricValues = $row['metricValues'] ?? [];
            $views = (int) round((float) ($metricValues[0]['value'] ?? 0));
            if ($views <= 0) {
                continue;
            }

            $engagementSeconds = (float) ($metricValues[1]['value'] ?? 0);
            $bounceRaw = (float) ($metricValues[2]['value'] ?? 0);
            $bounceRatePercent = $bounceRaw <= 1 ? $bounceRaw * 100 : $bounceRaw;

            if (!isset($resourceData[$resourceId])) {
                $resourceData[$resourceId] = [
                    'pagePath' => $pagePath,
                    'views' => 0,
                    'engagementSeconds' => 0.0,
                    'bounceWeighted' => 0.0,
                ];
            }

            $resourceData[$resourceId]['views'] += $views;
            $resourceData[$resourceId]['engagementSeconds'] += $engagementSeconds;
            $resourceData[$resourceId]['bounceWeighted'] += $bounceRatePercent * $views;
        }

        return $resourceData;
    }

    /**
     * @param array<int, array{pagePath: string, views: int, engagementSeconds: float, bounceWeighted: float}> $aggregated
     * @param array{
     *     enabled: bool,
     *     available: bool,
     *     error: string|null,
     *     periodDays: int,
     *     overview: array{totalViews: int, avgEngagementSeconds: float, bounceRate: float},
     *     topResources: array<int, array{resourceId: int, title: string, type: string, pagePath: string, views: int, avgEngagementSeconds: float, bounceRate: float}>,
     *     topCategories: array<int, array{label: string, views: int}>
     * } $baseResult
     * @return array{
     *     enabled: bool,
     *     available: bool,
     *     error: string|null,
     *     periodDays: int,
     *     overview: array{totalViews: int, avgEngagementSeconds: float, bounceRate: float},
     *     topResources: array<int, array{resourceId: int, title: string, type: string, pagePath: string, views: int, avgEngagementSeconds: float, bounceRate: float}>,
     *     topCategories: array<int, array{label: string, views: int}>
     * }
     */
    private function buildResult(array $aggregated, array $baseResult): array
    {
        if ([] === $aggregated) {
            return $baseResult;
        }

        $resources = $this->resourceRepository->findBy(['id' => array_keys($aggregated)]);
        $resourceMap = [];
        foreach ($resources as $resource) {
            if ($resource instanceof Resource && null !== $resource->getId()) {
                $resourceMap[$resource->getId()] = $resource;
            }
        }

        $topResources = [];
        $categoryViews = [];
        $totalViews = 0;
        $totalEngagementSeconds = 0.0;
        $totalBounceWeighted = 0.0;

        foreach ($aggregated as $resourceId => $item) {
            $views = (int) ($item['views'] ?? 0);
            if ($views <= 0) {
                continue;
            }

            $engagementSeconds = (float) ($item['engagementSeconds'] ?? 0);
            $bounceWeighted = (float) ($item['bounceWeighted'] ?? 0);
            $avgEngagement = $engagementSeconds / max(1, $views);
            $bounceRate = $bounceWeighted / max(1, $views);

            $resource = $resourceMap[$resourceId] ?? null;
            $title = $resource?->getTitle() ?? sprintf('Ressource #%d', $resourceId);
            $type = $resource?->getTypeLabel() ?? 'Autre';

            $topResources[] = [
                'resourceId' => $resourceId,
                'title' => $title,
                'type' => $type,
                'pagePath' => (string) ($item['pagePath'] ?? ''),
                'views' => $views,
                'avgEngagementSeconds' => round($avgEngagement, 2),
                'bounceRate' => round($bounceRate, 2),
            ];

            $categoryViews[$type] = ($categoryViews[$type] ?? 0) + $views;
            $totalViews += $views;
            $totalEngagementSeconds += $engagementSeconds;
            $totalBounceWeighted += $bounceWeighted;
        }

        usort($topResources, static fn (array $a, array $b): int => $b['views'] <=> $a['views']);
        $topResources = array_slice($topResources, 0, 8);

        arsort($categoryViews);
        $topCategories = [];
        foreach ($categoryViews as $label => $views) {
            $topCategories[] = [
                'label' => (string) $label,
                'views' => (int) $views,
            ];
        }

        $baseResult['overview'] = [
            'totalViews' => $totalViews,
            'avgEngagementSeconds' => round($totalEngagementSeconds / max(1, $totalViews), 2),
            'bounceRate' => round($totalBounceWeighted / max(1, $totalViews), 2),
        ];
        $baseResult['topResources'] = $topResources;
        $baseResult['topCategories'] = $topCategories;

        return $baseResult;
    }

    /**
     * @param array<string, mixed>|mixed $data
     */
    private function extractHttpErrorMessage(mixed $data): ?string
    {
        if (!is_array($data)) {
            return null;
        }

        if (isset($data['error']['message']) && is_string($data['error']['message'])) {
            return trim($data['error']['message']);
        }

        if (isset($data['message']) && is_string($data['message'])) {
            return trim($data['message']);
        }

        return null;
    }

    private function resolveCredentialsPath(string $value): string
    {
        $path = trim($value);
        if ('' === $path) {
            return '';
        }

        if (str_contains($path, '%kernel.project_dir%')) {
            $path = str_replace('%kernel.project_dir%', $this->projectDir, $path);
        }

        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return rtrim($this->projectDir, '/\\') . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }

    private function resolvePeriodDays(): int
    {
        return max(1, min(365, $this->ga4LookbackDays));
    }

    private function buildJwt(string $clientEmail, string $privateKey, string $tokenUri): string
    {
        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $clientEmail,
            'scope' => self::SCOPE,
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $headerEncoded = $this->base64UrlEncode((string) json_encode($header, JSON_UNESCAPED_SLASHES));
        $claimsEncoded = $this->base64UrlEncode((string) json_encode($claims, JSON_UNESCAPED_SLASHES));
        $unsignedToken = $headerEncoded . '.' . $claimsEncoded;

        $signature = '';
        $ok = openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            throw new \RuntimeException('Impossible de signer le JWT Google (private key invalide).');
        }

        return $unsignedToken . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

