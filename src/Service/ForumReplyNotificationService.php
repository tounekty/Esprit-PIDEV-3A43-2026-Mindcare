<?php

namespace App\Service;

use App\Entity\MessageForum;
use App\Entity\User;
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

        $recipients = [];
        $topicOwner = $topic->getUser();
        if ($topicOwner instanceof User) {
            $recipients[] = $topicOwner;
        }

        $parentAuthor = $reply->getParentMessage()?->getUser();
        if ($parentAuthor instanceof User) {
            $recipients[] = $parentAuthor;
        }

        $uniqueRecipients = [];
        foreach ($recipients as $recipient) {
            $recipientId = $recipient->getId();
            if ($recipientId === null || $recipientId === $author->getId()) {
                continue;
            }

            $uniqueRecipients[$recipientId] = $recipient;
        }

        foreach ($uniqueRecipients as $recipient) {
            $recipientEmail = $recipient->getEmail();
            if ($recipientEmail === null || $recipientEmail === '') {
                continue;
            }

            $recipientName = trim((string) ($recipient->getFirstName() ?? '') . ' ' . (string) ($recipient->getLastName() ?? ''));

            try {
                $this->mailer->send(
                    (new Email())
                        ->from('no-reply@mindcare.tn')
                        ->to($recipientEmail)
                        ->subject('Nouvelle réponse dans la discussion : ' . $topic->getTitre())
                        ->html($this->twig->render('emails/forum_reply_notification.html.twig', [
                            'ownerName' => $recipientName,
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
                    'recipient_id' => $recipient->getId(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
