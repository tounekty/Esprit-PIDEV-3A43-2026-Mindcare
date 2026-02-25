<?php

namespace App\Controller;

use Stichoza\GoogleTranslate\GoogleTranslate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class TranslationController extends AbstractController
{
    #[Route('/translate', name: 'translate', methods: ['POST'])]
    public function translate(Request $request): JsonResponse
    {
        $data   = json_decode($request->getContent(), true);
        $texts  = $data['texts']  ?? [];   // array of strings to translate
        $target = $data['target'] ?? 'fr'; // target language code

        $allowed = ['fr', 'en', 'ar', 'de', 'es', 'it'];
        if (!in_array($target, $allowed, true)) {
            return $this->json(['error' => 'Langue non supportée.'], 400);
        }

        if (empty($texts) || !is_array($texts)) {
            return $this->json(['error' => 'Aucun texte fourni.'], 400);
        }

        try {
            $tr = new GoogleTranslate($target);
            $translated = [];
            foreach ($texts as $key => $text) {
                $translated[$key] = $tr->translate((string) $text) ?? $text;
            }

            return $this->json(['translations' => $translated]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Service de traduction indisponible. Réessayez.'], 503);
        }
    }
}
