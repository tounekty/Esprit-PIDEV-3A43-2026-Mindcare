<?php

namespace App\Service;

use App\Entity\PsychologicalAlert;
use App\Entity\User;
use App\Entity\Mood;
use App\Entity\Commentaire;
use App\Repository\MoodRepository;
use App\Repository\CommentaireRepository;
use App\Repository\PsychologicalAlertRepository;
use Doctrine\ORM\EntityManagerInterface;

class PsychologicalAlertService
{
    private const DANGEROUS_KEYWORDS = [
        'fatigué de vivre',
        'je veux disparaître',
        'je veux mourir',
        'suicidaire',
        'me tuer',
        'finir',
        'en finir',
        'plus envie',
        'incurable',
        'sans espoir',
    ];

    private const NEGATIVE_MOODS = ['triste', 'colere', 'stresse'];

    public function __construct(
        private MoodRepository $moodRepository,
        private CommentaireRepository $commentaireRepository,
        private PsychologicalAlertRepository $alertRepository,
        private EntityManagerInterface $em,
        private NotificationService $notificationService,
    ) {}

    public function checkUserAlerts(User $user): void
    {
        $this->checkConsecutiveNegativeMoods($user);
        $this->checkDangerousKeywords($user);
    }

    private function checkConsecutiveNegativeMoods(User $user): void
    {
        $moods = $this->moodRepository
            ->createQueryBuilder('m')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.datemood', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        if (count($moods) < 5) {
            return;
        }

        $moods = array_reverse($moods);
        $consecutiveNegative = 0;
        $debugInfo = [];

        foreach ($moods as $mood) {
            $isNegative = in_array(strtolower($mood->getHumeur()), self::NEGATIVE_MOODS);
            $debugInfo[] = $mood->getHumeur() . " (" . ($isNegative ? "NEG" : "POS") . ")";
            
            if ($isNegative) {
                $consecutiveNegative++;
            } else {
                $consecutiveNegative = 0;
            }
        }

        if ($consecutiveNegative >= 5) {
            // Check if alert already exists
            $existingAlert = $this->alertRepository
                ->createQueryBuilder('p')
                ->where('p.user = :user')
                ->andWhere('p.alertType = :alertType')
                ->andWhere('p.resolved = false')
                ->setParameter('user', $user)
                ->setParameter('alertType', 'consecutive_negative_moods')
                ->getQuery()
                ->getOneOrNullResult();

            if (!$existingAlert) {
                $alert = new PsychologicalAlert();
                $alert->setUser($user);
                $alert->setAlertType('consecutive_negative_moods');
                $alert->setDescription('Humeur négative détectée pendant 5 jours consécutifs');
                $alert->setDetails(
                    'L\'utilisateur ' . $user->getFirstName() . ' a eu une humeur négative (triste, colère, stressé) pendant 5 jours consécutifs. Moods: ' . implode(', ', $debugInfo)
                );

                $this->em->persist($alert);
                $this->em->flush();

                $this->notifyAdminAndPsychologist($alert);
            }
        }
    }

    private function checkDangerousKeywords(User $user): void
    {
        $recentComments = $this->commentaireRepository
            ->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        foreach ($recentComments as $comment) {
            $content = strtolower($comment->getContenu());

            foreach (self::DANGEROUS_KEYWORDS as $keyword) {
                if (str_contains($content, strtolower($keyword))) {
                    // Check if alert already exists for this comment
                    $existingAlert = $this->alertRepository
                        ->createQueryBuilder('p')
                        ->where('p.user = :user')
                        ->andWhere('p.alertType = :alertType')
                        ->andWhere('p.resolved = false')
                        ->setParameter('user', $user)
                        ->setParameter('alertType', 'dangerous_keywords')
                        ->getQuery()
                        ->getOneOrNullResult();

                    if (!$existingAlert) {
                        $alert = new PsychologicalAlert();
                        $alert->setUser($user);
                        $alert->setAlertType('dangerous_keywords');
                        $alert->setDescription('Mot clé dangereux détecté dans un commentaire');
                        $alert->setDetails(
                            'Mot clé détecté: "' . $keyword . '" dans le commentaire de l\'utilisateur ' . $user->getFirstName()
                        );

                        $this->em->persist($alert);
                        $this->em->flush();

                        $this->notifyAdminAndPsychologist($alert);
                    }

                    return;
                }
            }
        }
    }

    private function notifyAdminAndPsychologist(PsychologicalAlert $alert): void
    {
        try {
            // Notify admins
            $this->notificationService->notifyAlert(
                'admin',
                'Alerte Psychologique',
                'Une alerte psychologique a été détectée pour l\'utilisateur: ' . ($alert->getUser()?->getEmail() ?? 'Unknown'),
                $alert
            );

            $alert->setNotifiedAdmin(true);

            // Notify psychologists
            $this->notificationService->notifyAlert(
                'psychologist',
                'Alerte Psychologique',
                'Une alerte psychologique a été détectée pour l\'utilisateur: ' . ($alert->getUser()?->getEmail() ?? 'Unknown'),
                $alert
            );

            $alert->setNotifiedPsychologist(true);

            $this->em->flush();
        } catch (\Exception $e) {
            // Log error but don't throw - alerts should not crash the app
            error_log('Error notifying about psychological alert: ' . $e->getMessage());
        }
    }

    public function resolveAlert(PsychologicalAlert $alert, string $adminNotes = ''): void
    {
        $alert->setResolved(true);
        $alert->setAdminNotes($adminNotes);
        $this->em->flush();
    }
}
