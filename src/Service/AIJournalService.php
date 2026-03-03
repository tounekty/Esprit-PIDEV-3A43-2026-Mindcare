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
        'fatigue' => [
            'keywords' => ['fatigué', 'fatigue', 'épuisé', 'épuisement', 'las', 'reposé', 'sommeil', 'dormir'],
            'suggestions' => [
                '😴 Accordez-vous une bonne nuit de sommeil - le repos est essentiel.',
                '☕ Prenez une pause - même 15 minutes de repos peuvent aider à recharger vos batteries.',
                '🧘 Essayez une méditation guidée pour vous détendre et relâcher la tension.',
                '🌿 Passez du temps dans la nature pour vous ressourcer.',
                '⏱️ Établissez des limites saines - apprenez à dire non et à prendre du temps pour vous.',
            ]
        ],
        'excite' => [
            'keywords' => ['excité', 'excite', 'enthousiaste', 'enthousiasme', 'passionné', 'passion', 'euphorie', 'énergie', 'énergique'],
            'suggestions' => [
                '🎉 Canalisez cette belle énergie dans un projet qui vous tient à cœur.',
                '👥 C\'est le moment parfait pour partager votre enthousiasme avec vos proches.',
                '⚡ Profitez de cette dynamique positive pour accomplir vos objectifs!',
            ]
        ],
        'neutre' => [
            'keywords' => ['neutre', 'normal', 'ordinaire', 'habituel', 'banal'],
            'suggestions' => [
                '💭 Continuez à explorer vos émotions - elles vous en diront plus.',
                '📔 Notez ce qui vous occupe en ce moment pour mieux vous comprendre.',
                '🎯 Fixez-vous un objectif personnel pour donner une direction à votre journée.',
                '🤔 Réfléchissez sur ce qui pourrait améliorer votre bien-être.',
                '🌟 Cherchez des moments de joie dans votre journée.',
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

    private const THERAPEUTIC_PROMPTS = [
        'heureux' => [
            "Qu'est-ce qui a le plus contribue a ce moment positif aujourd'hui ?",
            "Comment peux-tu recreer cette emotion positive dans les prochains jours ?",
            "Quelle petite victoire veux-tu celebrer ici ?",
        ],
        'triste' => [
            "Quelle pensee revient le plus souvent quand tu te sens triste ?",
            "De quoi as-tu le plus besoin en ce moment: soutien, repos, ou expression ?",
            "Si tu parlais a un ami dans la meme situation, que lui dirais-tu ?",
        ],
        'colere' => [
            "Quel evenement a declenche cette colere, concretement ?",
            "Quelle limite personnelle a ete depassee pour toi ?",
            "Quelle action calme et constructive peux-tu faire dans l'heure qui vient ?",
        ],
        'stresse' => [
            "Quelle est la principale source de stress aujourd'hui ?",
            "Quelles 2 actions simples peux-tu faire maintenant pour reduire la pression ?",
            "Qu'est-ce qui est sous ton controle, et qu'est-ce qui ne l'est pas ?",
        ],
        'calme' => [
            "Qu'est-ce qui t'a aide a atteindre cet etat de calme ?",
            "Comment peux-tu proteger ce calme demain ?",
            "Quelle habitude aimerais-tu garder pour rester aligne(e) ?",
        ],
        'fatigue' => [
            "Quelle est la cause principale de ta fatigue: sommeil, charge mentale ou corps ?",
            "De quoi ton corps a besoin ce soir pour recuperer ?",
            "Quelle tache peux-tu reporter pour te respecter aujourd'hui ?",
        ],
        'excite' => [
            "Quelle opportunite te donne cette energie en ce moment ?",
            "Comment canaliser cet elan vers une action concrete aujourd'hui ?",
            "Quel premier pas peux-tu faire tout de suite ?",
        ],
        'neutre' => [
            "Comment s'est passee ta journee, sans filtre ?",
            "Quel moment t'a marque, meme discretement ?",
            "De quoi as-tu besoin pour te sentir un peu mieux demain ?",
        ],
    ];

    public function analyzJournal(string $content, ?string $overrideSentiment = null): array
    {
        $sentiment = $overrideSentiment ?? $this->detectSentiment($content);
        
        return [
            'sentiment' => $sentiment,
            'keywords' => $this->extractKeywords($content),
            'suggestions' => $this->generateSuggestions($sentiment),
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

    private function generateSuggestions(string $sentiment): array
    {
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
            'fatigue' => '😴',
            'excite' => '🎉',
            'neutre' => '💭',
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
            'fatigue' => '#f39c12',
            'excite' => '#e67e22',
            'neutre' => '#95a5a6',
            default => '#95a5a6',
        };
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getTherapeuticPromptMap(): array
    {
        return self::THERAPEUTIC_PROMPTS;
    }

    /**
     * @return array<int, string>
     */
    public function getTherapeuticPrompts(?string $emotion): array
    {
        $normalized = $this->normalizeEmotionKey($emotion);
        return self::THERAPEUTIC_PROMPTS[$normalized] ?? self::THERAPEUTIC_PROMPTS['neutre'];
    }

    private function normalizeEmotionKey(?string $emotion): string
    {
        if (!is_string($emotion) || trim($emotion) === '') {
            return 'neutre';
        }

        $value = strtolower(trim($emotion));

        return match ($value) {
            'colere', 'colère' => 'colere',
            'stresse', 'stressé', 'stressée' => 'stresse',
            'fatigue', 'fatigué', 'fatiguée' => 'fatigue',
            'excite', 'excité', 'excitée' => 'excite',
            default => array_key_exists($value, self::THERAPEUTIC_PROMPTS) ? $value : 'neutre',
        };
    }
}
