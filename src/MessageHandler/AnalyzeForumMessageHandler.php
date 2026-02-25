<?php

namespace App\MessageHandler;

use App\Entity\MessageForum;
use App\Entity\MessageForumAnalysis;
use App\Message\AnalyzeForumMessage;
use App\Repository\MessageForumAnalysisRepository;
use App\Repository\MessageForumRepository;
use App\Service\ForumUrgencyAlertService;
use App\Service\HuggingFaceForumAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AnalyzeForumMessageHandler
{
    public function __construct(
        private readonly MessageForumRepository $messageForumRepository,
        private readonly MessageForumAnalysisRepository $analysisRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly HuggingFaceForumAnalysisService $analysisService,
        private readonly ForumUrgencyAlertService $urgencyAlertService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(AnalyzeForumMessage $command): void
    {
        $message = $this->messageForumRepository->find($command->getMessageId());
        if (!$message instanceof MessageForum) {
            return;
        }

        $analysis = $this->analysisRepository->findOneByMessage($message);
        if (!$analysis instanceof MessageForumAnalysis) {
            $analysis = (new MessageForumAnalysis())
                ->setMessage($message);

            $this->entityManager->persist($analysis);
        }

        $analysis
            ->setStatus(MessageForumAnalysis::STATUS_PENDING)
            ->setErrorMessage(null)
            ->setIsUrgent(false)
            ->setAnalyzedAt(null)
            ->touch();

        $this->entityManager->flush();

        try {
            $result = $this->analysisService->analyzeText($message->getContenu());

            $analysis
                ->setStatus(MessageForumAnalysis::STATUS_SUCCESS)
                ->setSentimentLabel($result['sentiment_label'])
                ->setSentimentScore($result['sentiment_score'])
                ->setUrgencyLabel($result['urgency_label'])
                ->setUrgencyScore($result['urgency_score'])
                ->setIsUrgent((bool) $result['is_urgent'])
                ->setModelName($result['model_name'])
                ->setRawResponse($result['raw_response'])
                ->setAnalyzedAt(new \DateTimeImmutable())
                ->setErrorMessage(null)
                ->touch();

            $this->entityManager->flush();

            if ($analysis->isUrgent()) {
                $this->urgencyAlertService->notifyTaggedPsychologuesOnUrgentMessage($message, $analysis);
            }
        } catch (\Throwable $exception) {
            $analysis
                ->setStatus(MessageForumAnalysis::STATUS_FAILED)
                ->setErrorMessage(mb_substr($exception->getMessage(), 0, 500))
                ->setAnalyzedAt(new \DateTimeImmutable())
                ->touch();

            $this->entityManager->flush();

            $this->logger->warning('Forum message AI analysis failed.', [
                'message_id' => $message->getId(),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
