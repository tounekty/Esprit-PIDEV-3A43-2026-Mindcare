<?php

namespace App\Controller;

use App\Service\ResourceChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    #[Route('/api/chatbot', name: 'api_chatbot', methods: ['POST'])]
    public function chat(
        Request $request,
        HuggingFaceService $huggingFaceService,
        EventRepository $eventRepository,
        EventReservationRepository $reservationRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userMessage = trim($data['message'] ?? '');

        if ($userMessage === '') {
            return $this->json(['error' => 'Message vide.'], 400);
        }

        // Build event statistics context for the AI
        $context = $this->buildEventContext($eventRepository, $reservationRepository);

        $systemPrompt = <<<PROMPT
Tu es un assistant intelligent pour MindCare, une plateforme de bien-être mental pour étudiants.
Tu aides les utilisateurs à consulter les statistiques des événements et à choisir les événements qui leur conviennent.
Réponds toujours en français, de manière concise et bienveillante.

Voici les données actuelles des événements :

{$context}

En te basant sur ces données :
- Donne des statistiques si on te le demande (taux de remplissage, places disponibles, événements populaires, etc.)
- Recommande des événements avec des places disponibles
- Compare des événements si nécessaire
- Donne des conseils sur les événements adaptés au bien-être étudiant
PROMPT;

        $reply = $huggingFaceService->chat($systemPrompt, $userMessage);

        return $this->json(['reply' => $reply]);
    }

    private function buildEventContext(EventRepository $eventRepository, EventReservationRepository $reservationRepository): string
    {
        $events = $eventRepository->findBy([], ['dateEvent' => 'ASC']);

        if (empty($events)) {
            return "Aucun événement disponible actuellement.";
        }

        $lines = [];
        $totalEvents = count($events);
        $totalCapacity = 0;
        $totalReservations = 0;
        $upcomingCount = 0;
        $fullCount = 0;
        $now = new \DateTime();

        foreach ($events as $event) {
            $activeCount = $reservationRepository->countActiveByEvent($event);
            $remaining = max(0, $event->getCapacite() - $activeCount);
            $fillRate = $event->getCapacite() > 0 ? round(($activeCount / $event->getCapacite()) * 100) : 0;
            $isPast = $event->getDateEvent() < $now;
            $status = $isPast ? 'Passé' : ($remaining === 0 ? 'Complet' : 'Disponible');

            $totalCapacity += $event->getCapacite();
            $totalReservations += $activeCount;
            if (!$isPast) $upcomingCount++;
            if ($remaining === 0 && !$isPast) $fullCount++;

            $lines[] = sprintf(
                "- \"%s\" | Catégorie: %s | Date: %s | Lieu: %s | Capacité: %d | Réservations: %d | Places restantes: %d | Taux remplissage: %d%% | Statut: %s",
                $event->getTitre(),
                $event->getCategorie() ?? 'Non catégorisé',
                $event->getDateEvent()->format('d/m/Y H:i'),
                $event->getLieu(),
                $event->getCapacite(),
                $activeCount,
                $remaining,
                $fillRate,
                $status
            );
        }

        $globalFillRate = $totalCapacity > 0 ? round(($totalReservations / $totalCapacity) * 100) : 0;

        $summary = "RÉSUMÉ GLOBAL: {$totalEvents} événements au total, {$upcomingCount} à venir, {$fullCount} complets. ";
        $summary .= "Capacité totale: {$totalCapacity}, Réservations totales: {$totalReservations}, Taux de remplissage global: {$globalFillRate}%.\n\n";
        $summary .= "LISTE DES ÉVÉNEMENTS:\n" . implode("\n", $lines);

        return $summary;
    }
}
