<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MeditationService
{
    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    /**
     * Récupère les méditations recommandées selon l'humeur
     */
    public function getMeditationsByMood(string $mood): array
    {
        $meditations = [
            'heureux' => [
                [
                    'title' => 'Gratitude Morning',
                    'duration' => '10 min',
                    'description' => 'Méditation de gratitude pour commencer la journée',
                    'youtubeId' => 'SnWDUW1SDWM',
                    'benefits' => ['Positivité', 'Bien-être', 'Énergie']
                ],
                [
                    'title' => 'Simple Meditation Technique',
                    'duration' => '8 min',
                    'description' => 'Technique simple pour maintenir la positivité',
                    'youtubeId' => 'inpok4MKVLM',
                    'benefits' => ['Sérénité', 'Présence', 'Joie']
                ],
                [
                    'title' => 'Joy & Happiness',
                    'duration' => '12 min',
                    'description' => 'Renforcer la sensation de bonheur',
                    'youtubeId' => 'rWBR-MhB3U4',
                    'benefits' => ['Bonheur', 'Confiance', 'Énergie']
                ]
            ],
            'triste' => [
                [
                    'title' => 'Healing Meditation',
                    'duration' => '15 min',
                    'description' => 'Méditation de guérison pour les moments difficiles',
                    'youtubeId' => '1ZYbU82FDwE',
                    'benefits' => ['Apaisement', 'Récupération', 'Acceptation']
                ],
                [
                    'title' => 'Guided Meditation for Sadness',
                    'duration' => '12 min',
                    'description' => 'Guide de méditation spéciale pour la tristesse',
                    'youtubeId' => 'O-6f5wQXSu8',
                    'benefits' => ['Réconfort', 'Légèreté', 'Hope']
                ],
                [
                    'title' => 'Loving Kindness Meditation',
                    'duration' => '10 min',
                    'description' => 'Bienveillance envers soi-même',
                    'youtubeId' => 'rWBR-MhB3U4',
                    'benefits' => ['Compassion', 'Auto-amour', 'Paix']
                ]
            ],
            'colere' => [
                [
                    'title' => 'Calm Your Mind',
                    'duration' => '10 min',
                    'description' => 'Calmer l\'esprit et relâcher la tension',
                    'youtubeId' => 'u9KZ-vVrUkY',
                    'benefits' => ['Relaxation', 'Lâcher-prise', 'Paix']
                ],
                [
                    'title' => 'Body Scan Relaxation',
                    'duration' => '20 min',
                    'description' => 'Relaxation progressive du corps',
                    'youtubeId' => 'U9YKP7ZD00I',
                    'benefits' => ['Détente', 'Conscience', 'Sérénité']
                ],
                [
                    'title' => 'Anger Release Meditation',
                    'duration' => '12 min',
                    'description' => 'Libérer la colère de façon saine',
                    'youtubeId' => 'inpok4MKVLM',
                    'benefits' => ['Libération', 'Clarté', 'Paix']
                ]
            ],
            'stresse' => [
                [
                    'title' => 'Stress Relief Meditation',
                    'duration' => '8 min',
                    'description' => 'Soulagement rapide du stress',
                    'youtubeId' => 'O6EolJFkqfA',
                    'benefits' => ['Détente', 'Clarté', 'Sérénité']
                ],
                [
                    'title' => 'Panic Attack Relief',
                    'duration' => '5 min',
                    'description' => 'Aide rapide pour les crises d\'anxiété',
                    'youtubeId' => 'ZToKcSJBAS8',
                    'benefits' => ['Apaisement', 'Contrôle', 'Paix']
                ],
                [
                    'title' => 'Progressive Relaxation',
                    'duration' => '15 min',
                    'description' => 'Relaxation progressive contre le stress',
                    'youtubeId' => 'U9YKP7ZD00I',
                    'benefits' => ['Relaxation', 'Lâcher-prise', 'Bien-être']
                ]
            ],
            'neutre' => [
                [
                    'title' => 'Basic Mindfulness',
                    'duration' => '10 min',
                    'description' => 'Méditation de pleine conscience basique',
                    'youtubeId' => 'inpok4MKVLM',
                    'benefits' => ['Présence', 'Conscience', 'Clarté']
                ],
                [
                    'title' => 'Body Awareness',
                    'duration' => '8 min',
                    'description' => 'Prise de conscience du corps',
                    'youtubeId' => '1ZYbU82FDwE',
                    'benefits' => ['Conscience', 'Connexion', 'Équilibre']
                ],
                [
                    'title' => 'Breathing Techniques',
                    'duration' => '6 min',
                    'description' => 'Techniques de respiration fondamentales',
                    'youtubeId' => 'rWBR-MhB3U4',
                    'benefits' => ['Calme', 'Équilibre', 'Énergie']
                ]
            ]
        ];

        return $meditations[$mood] ?? $meditations['neutre'];
    }

    /**
     * Récupère les méditations générales
     */
    public function getAllMeditations(): array
    {
        $all = [];
        foreach (['heureux', 'triste', 'colere', 'stresse', 'neutre'] as $mood) {
            $all = array_merge($all, $this->getMeditationsByMood($mood));
        }
        return $all;
    }

    /**
     * Récupère les affirmations positives
     */
    public function getAffirmations(): array
    {
        return [
            'Je suis capable de gérer mes émotions.',
            'Chaque jour est une nouvelle opportunité.',
            'Je mérite d\'être heureux et en paix.',
            'Mon bien-être est une priorité.',
            'Je suis fort et résilient.',
            'Mes émotions sont valides et compréhensibles.',
            'Je peux surmonter les défis.',
            'Je suis reconnaissant pour les petites choses.',
            'Ma santé mentale est importante.',
            'Je suis en contrôle de mes pensées.',
            'Je mérite de l\'amour et du respect.',
            'Chaque moment est une chance de croître.',
            'Je suis calme et serein.',
            'Ma vie a du sens et de l\'importance.',
            'Je suis libre de choisir mon bien-être.'
        ];
    }

    /**
     * Récupère les exercices de respiration
     */
    public function getBreathingExercises(): array
    {
        return [
            [
                'name' => '4-7-8 Breathing',
                'description' => 'Inspirez pendant 4 secondes, retenez 7 secondes, expirez 8 secondes',
                'benefits' => ['Calme', 'Sommeil', 'Anxiété'],
                'duration' => '5 min'
            ],
            [
                'name' => 'Box Breathing',
                'description' => 'Inspirez 4s, retenez 4s, expirez 4s, retenez 4s',
                'benefits' => ['Équilibre', 'Clarté', 'Détente'],
                'duration' => '5 min'
            ],
            [
                'name' => 'Belly Breathing',
                'description' => 'Respirez profondément en gonflant le ventre',
                'benefits' => ['Relaxation', 'Ancrage', 'Présence'],
                'duration' => '3 min'
            ]
        ];
    }
}
