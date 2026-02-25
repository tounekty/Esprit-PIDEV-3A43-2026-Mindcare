<?php

namespace App\Controller;

use App\Service\ResourceChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    /**
     * POST /chatbot/resource
     * Body JSON: { "message": "...", "history": [{role, content}, ...] }
     */
    #[Route('/chatbot/resource', name: 'chatbot_resource', methods: ['POST'])]
    public function chat(Request $request, ResourceChatbotService $chatbotService): JsonResponse
    {
        $data    = json_decode((string) $request->getContent(), true) ?? [];
        $message = trim((string) ($data['message'] ?? ''));
        $history = is_array($data['history'] ?? null) ? $data['history'] : [];

        if ($message === '') {
            return $this->json(['error' => 'Message vide.'], 400);
        }

        // Keep last 10 turns max to avoid token overflow
        $history = array_slice($history, -20);

        $result = $chatbotService->chat($message, $history);

        if ($result['error'] !== null) {
            return $this->json(['error' => $result['error']], 503);
        }

        return $this->json(['reply' => $result['reply']]);
    }
}
