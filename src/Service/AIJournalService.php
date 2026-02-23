<?php

namespace App\Service;

class AIJournalService
{
    // Patterns et suggestions basées sur les émotions
    private const EMOTION_PATTERNS = [
        'triste' => [
            'keywords' => ['seul', 'déprimé', 'noir', 'pleurer', 'mal', 'mal-être', 'vide', 'perte'],
            'suggestions' => [
                '🎵 Essayez d\'écouter votre musique préférée pour vous remonter le moral.',
                '🚶 Une promenade en nature peut vraiment vous aider à vous sentir mieux.',
                '👥 Appelez un ami ou un proche - parler aide à se sentir moins seul.',
                '📖 Lisez quelque chose d\'inspirant pour changer votre perspective.',
                '🧘 Essayez une méditation courte (5-10 minutes) pour calmer votre esprit.',
            ]
        ],
        'stressé' => [
            'keywords' => ['stress', 'anxieux', 'tendu', 'inquiet', 'préoccupé', 'panique', 'angoisse', 'nerveux'],
            'suggestions' => [
                '🧘 La respiration profonde : inspirez 4 secondes, expirez 4 secondes.',
                '💪 Faites de l\'exercice - même 10 minutes d\'étirement peuvent réduire le stress.',
                '📝 Notez vos préoccupations pour les organiser mentalement.',
                '🎯 Décomposez vos problèmes en tâches plus petites et gérables.',
                '☕ Prenez du temps pour vous - un café, un bain relaxant, etc.',
            ]
        ],
        'colère' => [
            'keywords' => ['angry', 'furieux', 'énervé', 'rage', 'irrité', 'frustré', 'colère', 'agressif'],
            'suggestions' => [
                '🏃 Canalisez votre énergie - courez, frappez un punching-ball ou faites du sport.',
                '📖 Tenez un journal de votre colère - écrivez sans filtre, puis relisez plus tard.',
                '🧊 Prenez une douche froide pour calmer votre système nerveux.',
                '🎤 Criez quelque part en privé - libérez cette énergie négative.',
                '💬 Parlez à quelqu\'un de confiance pour exprimer votre frustration.',
            ]
        ],
        'heureux' => [
            'keywords' => ['heureux', 'joie', 'content', 'rire', 'merveilleux', 'magnifique', 'amour', 'succès'],
            'suggestions' => [
                '📸 Capturez ce moment heureux - prenez une photo ou un selfie.',
                '🎉 Partagez votre bonheur avec quelqu\'un - appelez un ami ou une famille.',
                '📔 Notez ce qui vous a rendu heureux pour vous en souvenir plus tard.',
                '🙏 Soyez reconnaissant - remerciez une personne qui contribue à votre bonheur.',
                '🌟 Célébrez ce moment - vous le méritez !',
            ]
        ],
        'calme' => [
            'keywords' => ['calme', 'zen', 'sérein', 'paix', 'tranquille', 'équilibre', 'serein', 'stable'],
            'suggestions' => [
                '🧘 Maintenez cette paix intérieure avec une méditation quotidienne.',
                '📚 Profitez de votre sérénité pour lire ou apprendre quelque chose de nouveau.',
                '🌿 Restez connecté à la nature pour préserver cette sérénité.',
                '⏰ Établissez une routine saine pour maintenir cet équilibre émotionnel.',
                '💭 Reflétchissez sur ce qui vous a amené à cet état de calme.',
            ]
        ],
    ];

    private const WELLNESS_TIPS = [
        '💧 Restez hydraté - buvez au moins 8 verres d\'eau par jour.',
        '😴 Dormez suffisamment - 7-8 heures par nuit est l\'idéal.',
        '🥗 Mangez équilibré - évitez les sucres et privilégiez les fruits/légumes.',
        '🏃 Bougez quotidiennement - même 30 minutes de marche aide.',
        '📵 Limitez les écrans avant de dormir - ça aide à mieux dormir.',
        '🧠 Pratiquez la gratitude - écrivez 3 choses pour lesquelles vous êtes reconnaissant.',
        '🤝 Passez du temps avec vos proches - les relations sociales sont essentielles.',
        '🎨 Créez quelque chose - l\'art, la musique ou l\'écriture peuvent être thérapeutiques.',
    ];

    public function analyzJournal(string $content): array
    {
        return [
            'sentiment' => $this->detectSentiment($content),
            'keywords' => $this->extractKeywords($content),
            'suggestions' => $this->generateSuggestions($content),
            'wellness_tip' => $this->getRandomWellnessTip(),
        ];
    }

    private function detectSentiment(string $content): string
    {
        $content = strtolower($content);
        $scores = [];

        foreach (self::EMOTION_PATTERNS as $emotion => $data) {
            $score = 0;
            foreach ($data['keywords'] as $keyword) {
                if (str_contains($content, $keyword)) {
                    $score += substr_count($content, $keyword);
                }
            }
            $scores[$emotion] = $score;
        }

        arsort($scores);
        $topEmotion = array_key_first($scores);
        
        return !empty($topEmotion) ? $topEmotion : 'neutre';
    }

    private function extractKeywords(string $content): array
    {
        $content = strtolower($content);
        $keywords = [];

        foreach (self::EMOTION_PATTERNS as $emotion => $data) {
            foreach ($data['keywords'] as $keyword) {
                if (str_contains($content, $keyword)) {
                    $keywords[] = $keyword;
                }
            }
        }

        return array_unique($keywords);
    }

    private function generateSuggestions(string $content): array
    {
        $sentiment = $this->detectSentiment($content);
        
        if (!isset(self::EMOTION_PATTERNS[$sentiment])) {
            return ['💭 Continuez à exprimer vos sentiments - le journal est votre espace sûr.'];
        }

        $suggestions = self::EMOTION_PATTERNS[$sentiment]['suggestions'];
        shuffle($suggestions);
        return array_slice($suggestions, 0, 3);
    }

    private function getRandomWellnessTip(): string
    {
        $tips = self::WELLNESS_TIPS;
        return $tips[array_rand($tips)];
    }

    public function getSentimentEmoji(string $sentiment): string
    {
        return match ($sentiment) {
            'triste' => '😢',
            'stressé' => '😰',
            'colère' => '😠',
            'heureux' => '😊',
            'calme' => '🧘',
            default => '💭',
        };
    }

    public function getSentimentColor(string $sentiment): string
    {
        return match ($sentiment) {
            'triste' => '#3498db',
            'stressé' => '#e74c3c',
            'colère' => '#c0392b',
            'heureux' => '#2ecc71',
            'calme' => '#9b59b6',
            default => '#95a5a6',
        };
    }
}
