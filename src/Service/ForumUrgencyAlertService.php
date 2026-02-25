<?php

namespace App\Service;

use App\Entity\MessageForum;
use App\Entity\MessageForumAnalysis;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class ForumUrgencyAlertService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notifyTaggedPsychologuesOnUrgentMessage(MessageForum $message, MessageForumAnalysis $analysis): void
    {
        $sujet = $message->getSujet();
        if ($sujet === null) {
            return;
        }

        $author = $message->getUser();
        $authorId = $author?->getId();
        $authorName = $message->isAnonymous() ? 'Anonyme' : trim((string) ($author?->getFirstName() ?? '') . ' ' . (string) ($author?->getLastName() ?? ''));
        if ($authorName === '') {
            $authorName = $message->isAnonymous() ? 'Anonyme' : 'Utilisateur';
        }

        $topicUrl = $this->urlGenerator->generate('front_forum_show', [
            'id' => $sujet->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL) . '#message-' . $message->getId();

        $excerpt = trim(strip_tags($message->getContenu()));
        if (mb_strlen($excerpt) > 260) {
            $excerpt = mb_substr($excerpt, 0, 260) . '...';
        }

        foreach ($sujet->getTaggedPsychologues() as $psychologue) {
            if (!$psychologue instanceof User || $psychologue->getRole() !== 'psychologue') {
                continue;
            }

            if ($authorId !== null && $psychologue->getId() === $authorId) {
                continue;
            }

            $email = $psychologue->getEmail();
            if ($email === null || $email === '') {
                continue;
            }

            $psychologueName = trim((string) ($psychologue->getFirstName() ?? '') . ' ' . (string) ($psychologue->getLastName() ?? ''));

            try {
                $this->mailer->send(
                    (new Email())
                        ->from('no-reply@mindcare.tn')
                        ->to($email)
                        ->subject('⚠️ Message potentiellement urgent détecté')
                        ->html($this->twig->render('emails/forum_urgent_message_alert.html.twig', [
                            'psychologueName' => $psychologueName,
                            'topicTitle' => $sujet->getTitre(),
                            'authorName' => $authorName,
                            'excerpt' => $excerpt,
                            'urgencyScore' => $analysis->getUrgencyScore(),
                            'sentimentLabel' => $analysis->getSentimentLabel(),
                            'topicUrl' => $topicUrl,
                        ]))
                );
            } catch (\Throwable $exception) {
                $this->logger->warning('Forum urgent alert email failed.', [
                    'message_id' => $message->getId(),
                    'psychologue_id' => $psychologue->getId(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
