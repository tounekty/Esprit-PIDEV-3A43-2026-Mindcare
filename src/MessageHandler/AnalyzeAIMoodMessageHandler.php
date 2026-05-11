<?php

namespace App\MessageHandler;

use App\Entity\Mood;
use App\Message\AnalyzeAIMoodMessage;
use App\Repository\MoodRepository;
use App\Service\AIJournalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AnalyzeAIMoodMessageHandler
{
    public function __construct(
        private MoodRepository $moodRepository,
        private AIJournalService $aiService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(AnalyzeAIMoodMessage $message): void
    {
        $mood = $this->moodRepository->find($message->getMoodId());
        
        if (!$mood) {
            return;
        }

        // Perform AI analysis
        $analysis = $this->aiService->analyzJournal($message->getHumeur());
        
        // Convert array result to JSON string for storage
        $analysisJson = json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Store the analysis result in the mood
        $mood->setAiAnalysis($analysisJson);
        
        $this->entityManager->persist($mood);
        $this->entityManager->flush();
    }
}
