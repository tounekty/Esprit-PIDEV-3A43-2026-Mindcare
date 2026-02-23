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
            // Find users with the specified role
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
            <h2>🚨 Alerte Psychologique</h2>
        </div>
        
        <div class="content">
            <p><strong>Message:</strong> {$message}</p>
            
            <div class="user-info">
                <h3>Informations sur l'utilisateur</h3>
                <p><strong>Nom d'utilisateur:</strong> {$alertUser?->getFirstName()} {$alertUser?->getLastName()}</p>
                <p><strong>Email:</strong> {$alertUser?->getEmail()}</p>
                <p><strong>Type d'alerte:</strong> <span class="alert-type">{$this->formatAlertType($alertType)}</span></p>
                <p><strong>Description:</strong> {$description}</p>
                <p><strong>Détails:</strong> {$details}</p>
                <p><strong>Date d'alerte:</strong> {$alert->getCreatedAt()?->format('d/m/Y H:i:s')}</p>
            </div>
            
            <p><strong>Action requise:</strong> Veuillez consulter le système MindCare pour plus de détails et prendre les mesures nécessaires.</p>
        </div>
        
        <div class="footer">
            <p>Cet email a été envoyé automatiquement par le système d'alerte psychologique de MindCare.</p>
        </div>
    </div>
</body>
</html>
HTML;

        $email = (new Email())
            ->from('alerts@mindcare.local')
            ->to($user->getEmail())
            ->subject($subject)
            ->html($emailBody);

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log('Error sending notification email: ' . $e->getMessage());
        }
    }

    private function formatAlertType(string $alertType): string
    {
        return match ($alertType) {
            'consecutive_negative_moods' => 'Humeur négative consécutive',
            'dangerous_keywords' => 'Mots clés dangereux détectés',
            default => $alertType,
        };
    }
}
