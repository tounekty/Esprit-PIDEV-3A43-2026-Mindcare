<?php

namespace App\Bundles\EventNotificationBundle\Service;

use App\Entity\Event;
use App\Entity\EventReservation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer les notifications d'événements
 */
class EventNotificationService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private string $mailerFrom;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        string $mailerFrom = 'noreply@mindcare.com'
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->mailerFrom = $mailerFrom;
    }

    /**
     * Envoie une notification de confirmation de réservation
     */
    public function notifyReservationConfirmed(EventReservation $reservation): bool
    {
        try {
            $event = $reservation->getEvent();
            $user = $reservation->getUser();

            $email = (new Email())
                ->from($this->mailerFrom)
                ->to($user->getEmail())
                ->subject('Confirmation de votre réservation - ' . $event->getTitre())
                ->html($this->renderReservationConfirmationEmail($event, $user));

            $this->mailer->send($email);
            $this->logger->info('Réservation confirmée pour l\'utilisateur ' . $user->getId());

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de la notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie une notification d'annulation de réservation
     */
    public function notifyReservationCancelled(EventReservation $reservation): bool
    {
        try {
            $event = $reservation->getEvent();
            $user = $reservation->getUser();

            $email = (new Email())
                ->from($this->mailerFrom)
                ->to($user->getEmail())
                ->subject('Annulation de réservation - ' . $event->getTitre())
                ->html($this->renderReservationCancelledEmail($event, $user));

            $this->mailer->send($email);
            $this->logger->info('Notification d\'annulation envoyée pour l\'utilisateur ' . $user->getId());

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de la notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie une notification aux places disponibles
     */
    public function notifyEventAvailableSpots(Event $event): bool
    {
        try {
            // À implémenter selon votre logique métier
            $this->logger->info('Notification de places disponibles pour l\'événement ' . $event->getId());
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de la notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie une notification de promotion depuis la liste d'attente
     */
    public function notifyPromotedFromWaiting(EventReservation $reservation): bool
    {
        try {
            $event = $reservation->getEvent();
            $user = $reservation->getUser();

            $email = (new Email())
                ->from($this->mailerFrom)
                ->to($user->getEmail())
                ->subject('🎉 Vous avez été promu ! - ' . $event->getTitre())
                ->html($this->renderPromotionEmail($event, $user));

            $this->mailer->send($email);
            $this->logger->info('Notification de promotion envoyée pour l\'utilisateur ' . $user->getId());

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de la notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie une notification pour la liste d'attente
     */
    public function notifyAddedToWaitingList(EventReservation $reservation, int $position): bool
    {
        try {
            $event = $reservation->getEvent();
            $user = $reservation->getUser();

            $email = (new Email())
                ->from($this->mailerFrom)
                ->to($user->getEmail())
                ->subject('Liste d\'attente - ' . $event->getTitre())
                ->html($this->renderWaitingListEmail($event, $user, $position));

            $this->mailer->send($email);
            $this->logger->info('Notification de liste d\'attente envoyée pour l\'utilisateur ' . $user->getId() . ' à la position ' . $position);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de la notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Rend le template d'email de confirmation
     */
    private function renderReservationConfirmationEmail(Event $event, mixed $user): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); color: white; padding: 20px; border-radius: 8px; }
        .content { padding: 20px 0; }
        .footer { border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Réservation Confirmée</h1>
        </div>
        <div class="content">
            <p>Bonjour {$user->getFirstName()},</p>
            <p>Votre réservation pour l'événement <strong>{$event->getTitre()}</strong> a été confirmée!</p>
            <p><strong>Détails:</strong></p>
            <ul>
                <li>Date et heure: {$event->getDateEvent()->format('d/m/Y H:i')}</li>
                <li>Lieu: {$event->getLieu()}</li>
                <li>Description: {$event->getDescription()}</li>
            </ul>
            <p>Merci de votre inscription!</p>
        </div>
        <div class="footer">
            <p>© 2026 MindCare - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Rend le template d'email d'annulation
     */
    private function renderReservationCancelledEmail(Event $event, mixed $user): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #ef4444 0%, #f87171 100%); color: white; padding: 20px; border-radius: 8px; }
        .content { padding: 20px 0; }
        .footer { border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Réservation Annulée</h1>
        </div>
        <div class="content">
            <p>Bonjour {$user->getFirstName()},</p>
            <p>Votre réservation pour l'événement <strong>{$event->getTitre()}</strong> a été annulée.</p>
            <p>Si vous avez des questions, veuillez nous contacter.</p>
        </div>
        <div class="footer">
            <p>© 2026 MindCare - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Rend le template d'email de promotion
     */
    private function renderPromotionEmail(Event $event, mixed $user): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #10b981 0%, #34d399 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; }
        .content { padding: 20px 0; }
        .highlight { background: #dcfce7; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981; }
        .footer { border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Vous avez été promu !</h1>
        </div>
        <div class="content">
            <p>Bonjour {$user->getFirstName()},</p>
            <div class="highlight">
                <p><strong>Bonne nouvelle !</strong> Une place s'est libérée. Vous avez été promu d'office depuis la liste d'attente pour l'événement <strong>{$event->getTitre()}</strong>.</p>
            </div>
            <p><strong>Détails de l'événement:</strong></p>
            <ul>
                <li>Date et heure: {$event->getDateEvent()->format('d/m/Y H:i')}</li>
                <li>Lieu: {$event->getLieu()}</li>
                <li>Capacité: {$event->getCapacite()} personnes</li>
            </ul>
            <p>Merci de votre intérêt pour cet événement !</p>
        </div>
        <div class="footer">
            <p>© 2026 MindCare - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Rend le template d'email de liste d'attente
     */
    private function renderWaitingListEmail(Event $event, mixed $user, int $position): string
    {
        $ordinal = $this->getOrdinal($position);
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); color: white; padding: 20px; border-radius: 8px; }
        .content { padding: 20px 0; }
        .waiting-box { background: #fef3c7; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b; }
        .position { font-size: 32px; font-weight: bold; color: #f59e0b; }
        .footer { border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⏳ Vous êtes en liste d'attente</h1>
        </div>
        <div class="content">
            <p>Bonjour {$user->getFirstName()},</p>
            <div class="waiting-box">
                <p>L'événement <strong>{$event->getTitre()}</strong> est actuellement complet.</p>
                <p>Vous avez été ajouté à la liste d'attente à la position:</p>
                <div class="position">{$ordinal}</div>
                <p>Si une place se libère, vous serez automatiquement notifié et promulgé.</p>
            </div>
            <p><strong>Détails de l'événement:</strong></p>
            <ul>
                <li>Date et heure: {$event->getDateEvent()->format('d/m/Y H:i')}</li>
                <li>Lieu: {$event->getLieu()}</li>
                <li>Capacité: {$event->getCapacite()} personnes</li>
            </ul>
            <p>Nous vous remercions de votre intérêt !</p>
        </div>
        <div class="footer">
            <p>© 2026 MindCare - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Convertit un nombre en ordinal français (1er, 2e, 3e, etc.)
     */
    private function getOrdinal(int $number): string
    {
        if ($number === 1) {
            return '1<sup>er</sup>';
        }
        return $number . '<sup>e</sup>';
    }
}
