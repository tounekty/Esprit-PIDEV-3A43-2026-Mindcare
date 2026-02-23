<?php

namespace App\Service;

use App\Entity\MessageForum;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class ForumReplyNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notifyTopicOwnerOnReply(MessageForum $reply): void
    {
        $topic = $reply->getSujet();
        $author = $reply->getUser();

        if ($topic === null || $author === null) {
            return;
        }

        $topicOwner = $topic->getUser();
        if ($topicOwner === null) {
            return;
        }

        $recipientEmail = $topicOwner->getEmail();
        if ($recipientEmail === null || $recipientEmail === '') {
            return;
        }

        if ($topicOwner->getId() === $author->getId()) {
            return;
        }

        $authorName = trim((string) ($author->getFirstName() ?? '') . ' ' . (string) ($author->getLastName() ?? ''));
        if ($authorName === '') {
            $authorName = $author->getEmail() ?? 'Un utilisateur';
        }

        $preview = trim(strip_tags($reply->getContenu()));
        if (mb_strlen($preview) > 220) {
            $preview = mb_substr($preview, 0, 220) . '...';
        }

        $topicUrl = $this->urlGenerator->generate('front_forum_show', [
            'id' => $topic->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            $this->mailer->send(
                (new Email())
                    ->from('no-reply@mindcare.tn')
                    ->to($recipientEmail)
                    ->subject('Nouvelle réponse à votre sujet : ' . $topic->getTitre())
                    ->html($this->twig->render('emails/forum_reply_notification.html.twig', [
                        'ownerName' => trim((string) ($topicOwner->getFirstName() ?? '') . ' ' . (string) ($topicOwner->getLastName() ?? '')),
                        'topicTitle' => $topic->getTitre(),
                        'authorName' => $authorName,
                        'messagePreview' => $preview,
                        'topicUrl' => $topicUrl,
                    ]))
            );
        } catch (\Throwable $exception) {
            $this->logger->warning('Forum reply notification email failed.', [
                'topic_id' => $topic->getId(),
                'reply_id' => $reply->getId(),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
