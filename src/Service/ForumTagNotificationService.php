<?php

namespace App\Service;

use App\Entity\SujetForum;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class ForumTagNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notifyTaggedPsychologuesOnTopicCreation(SujetForum $sujet): void
    {
        $author = $sujet->getUser();
        if (!$author instanceof User) {
            return;
        }

        $authorDisplayName = 'Anonyme';
        if (!$sujet->isAnonymous()) {
            $authorDisplayName = trim((string) ($author->getFirstName() ?? '') . ' ' . (string) ($author->getLastName() ?? ''));
            if ($authorDisplayName === '') {
                $authorDisplayName = $author->getEmail() ?? 'Utilisateur';
            }
        }

        $topicUrl = $this->urlGenerator->generate('front_forum_show', [
            'id' => $sujet->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $excerpt = trim(strip_tags($sujet->getDescription()));
        if (mb_strlen($excerpt) > 240) {
            $excerpt = mb_substr($excerpt, 0, 240) . '...';
        }

        foreach ($sujet->getTaggedPsychologues() as $psychologue) {
            if (!$psychologue instanceof User) {
                continue;
            }

            if ($psychologue->getRole() !== 'psychologue') {
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
                        ->subject('Vous avez été tagué(e) dans un sujet : ' . $sujet->getTitre())
                        ->html($this->twig->render('emails/forum_tagged_psychologue.html.twig', [
                            'psychologueName' => $psychologueName,
                            'topicTitle' => $sujet->getTitre(),
                            'authorName' => $authorDisplayName,
                            'topicExcerpt' => $excerpt,
                            'topicUrl' => $topicUrl,
                        ]))
                );
            } catch (\Throwable $exception) {
                $this->logger->warning('Forum tag notification email failed.', [
                    'topic_id' => $sujet->getId(),
                    'psychologue_id' => $psychologue->getId(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
