<?php

namespace App\Service;

use App\Entity\PsychologicalAlert;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private UserRepository $userRepository,
    ) {}

    public function notifyAlert(string $role, string $subject, string $message, PsychologicalAlert $alert): void
    {
        try {
            $users = $this->userRepository->findByRole($role);

            foreach ($users as $user) {
                if ($user->getEmail()) {
                    $this->sendEmailNotification($user, $subject, $message, $alert);
                }
            }
        } catch (\Exception $e) {
            error_log('Error notifying about alert: ' . $e->getMessage());
        }
    }

    public function notifyStudentAfterAlertResolution(PsychologicalAlert $alert, string $adminNotes = ''): void
    {
        try {
            $student = $alert->getUser();
            if (!$student || !$student->getEmail()) {
                return;
            }

            $subject = 'MindCare - Suivi bien-etre et prochaines etapes';
            $this->sendStudentFollowUpEmail($student, $alert, $adminNotes, $subject);
        } catch (\Exception $e) {
            error_log('Error notifying student after alert resolution: ' . $e->getMessage());
        }
    }

    private function sendEmailNotification(User $user, string $subject, string $message, PsychologicalAlert $alert): void
    {
        $alertUser = $alert->getUser();
        $alertType = $alert->getAlertType();
        $description = $alert->getDescription();
        $details = $alert->getDetails();

        $emailBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #d32f2f; color: white; padding: 20px; border-radius: 5px; text-align: center; }
        .content { background: #f5f5f5; padding: 20px; border-left: 4px solid #d32f2f; margin-top: 20px; }
        .alert-type { font-weight: bold; color: #d32f2f; }
        .user-info { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .footer { margin-top: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Alerte Psychologique</h2>
        </div>
        <div class="content">
            <p><strong>Message:</strong> {$message}</p>
            <div class="user-info">
                <h3>Informations sur l'utilisateur</h3>
                <p><strong>Nom d'utilisateur:</strong> {$alertUser?->getFirstName()} {$alertUser?->getLastName()}</p>
                <p><strong>Email:</strong> {$alertUser?->getEmail()}</p>
                <p><strong>Type d'alerte:</strong> <span class="alert-type">{$this->formatAlertType($alertType)}</span></p>
                <p><strong>Description:</strong> {$description}</p>
                <p><strong>Details:</strong> {$details}</p>
                <p><strong>Date d'alerte:</strong> {$alert->getCreatedAt()?->format('d/m/Y H:i:s')}</p>
            </div>
            <p><strong>Action requise:</strong> Veuillez consulter MindCare pour plus de details.</p>
        </div>
        <div class="footer">
            <p>Email automatique du systeme d'alerte psychologique MindCare.</p>
        </div>
    </div>
</body>
</html>
HTML;

        $email = (new Email())
            ->from($this->getFromAddress())
            ->to($user->getEmail())
            ->subject($subject)
            ->html($emailBody);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log('Error sending notification email: ' . $e->getMessage());
        }
    }

    private function sendStudentFollowUpEmail(User $student, PsychologicalAlert $alert, string $adminNotes, string $subject): void
    {
        $studentName = trim((string) ($student->getFirstName() . ' ' . $student->getLastName()));
        if ($studentName === '') {
            $studentName = 'Etudiant(e)';
        }

        $typeLabel = $this->formatAlertType($alert->getAlertType());
        $safeNotes = trim($adminNotes) !== ''
            ? nl2br(htmlspecialchars($adminNotes, ENT_QUOTES, 'UTF-8'))
            : 'Nous vous recommandons de planifier un rendez-vous avec un psychologue pour un accompagnement personnalise.';

        $emailBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #2d3748; }
        .container { max-width: 640px; margin: 0 auto; padding: 20px; }
        .header { background: #2b6cb0; color: #fff; padding: 18px; border-radius: 8px; }
        .content { margin-top: 16px; background: #f7fafc; border-left: 4px solid #2b6cb0; padding: 18px; border-radius: 6px; }
        .tips { background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px; margin-top: 14px; }
        .cta { margin-top: 14px; font-weight: bold; color: #1a202c; }
        .footer { margin-top: 16px; font-size: 12px; color: #718096; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin:0;">Suivi Bien-etre MindCare</h2>
        </div>
        <div class="content">
            <p>Bonjour {$studentName},</p>
            <p>Suite a votre activite recente ({$typeLabel}), nous vous invitons a prendre soin de vous des maintenant.</p>
            <p><strong>Message de suivi:</strong> {$safeNotes}</p>

            <div class="tips">
                <p><strong>Conseils recommandes:</strong></p>
                <ul>
                    <li>Prenez un rendez-vous avec un psychologue depuis votre espace MindCare.</li>
                    <li>Faites une pause respiration de 5 minutes, 2 a 3 fois par jour.</li>
                    <li>Maintenez une routine simple: sommeil, hydratation, et journal emotionnel.</li>
                </ul>
            </div>

            <p class="cta">Action conseillee: connectez-vous a MindCare et planifiez un RDV.</p>
        </div>
        <div class="footer">
            <p>Ceci est un email automatique. En cas d'urgence, contactez immediatement les services d'urgence locaux.</p>
        </div>
    </div>
</body>
</html>
HTML;

        $email = (new Email())
            ->from($this->getFromAddress())
            ->to($student->getEmail())
            ->subject($subject)
            ->html($emailBody);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log('Error sending student follow-up email: ' . $e->getMessage());
        }
    }

    private function formatAlertType(string $alertType): string
    {
        return match ($alertType) {
            'consecutive_negative_moods' => 'humeurs negatives consecutives',
            'dangerous_keywords' => 'mots cles sensibles detectes',
            default => $alertType,
        };
    }

    private function getFromAddress(): string
    {
        return $_ENV['MAILER_FROM_ADDRESS']
            ?? $_SERVER['MAILER_FROM_ADDRESS']
            ?? 'nawreshichri0@gmail.com';
    }
}
