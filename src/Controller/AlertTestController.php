<?php

namespace App\Controller;

use App\Service\PsychologicalAlertService;
use App\Repository\UserRepository;
use App\Repository\MoodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test')]
final class AlertTestController extends AbstractController
{
    public function __construct(
        private MoodRepository $moodRepository
    ) {}

    #[Route('/alerts/check', name: 'test_check_alerts', methods: ['GET'])]
    public function checkAlerts(PsychologicalAlertService $alertService): Response
    {
        // Get current user
        $user = $this->getUser();
        
        if (!$user) {
            return new Response('❌ Vous devez être connecté d\'abord. <br><a href="/login">Se connecter</a>', 403);
        }

        try {
            $alertService->checkUserAlerts($user);
            return new Response('✅ Alertes vérifiées avec succès pour ' . $user->getFirstName() . '<br><a href="/admin/psychological-alerts">Voir les alertes</a>');
        } catch (\Exception $e) {
            return new Response('❌ Erreur: ' . $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>', 500);
        }
    }

    #[Route('/debug2', name: 'test_debug2', methods: ['GET'])]
    public function debug2(PsychologicalAlertService $alertService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return new Response('Non connecté');
        }

        // Afficher les 5 derniers moods
        $moods = $this->moodRepository
            ->createQueryBuilder('m')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.datemood', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $result = "Utilisateur: " . $user->getEmail() . "<br>";
        $result .= "Moods trouvés: " . count($moods) . "<br><br>";

        foreach ($moods as $mood) {
            $result .= "ID: " . $mood->getId() . " | Humeur: " . $mood->getHumeur() . " | Date: " . $mood->getDatemood()->format('Y-m-d') . "<br>";
        }

        return new Response($result);
    }
}