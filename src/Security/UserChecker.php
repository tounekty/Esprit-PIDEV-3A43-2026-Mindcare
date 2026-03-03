<?php
// src/Security/UserChecker.php
namespace App\Security;

use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserChecker implements UserCheckerInterface
{
    /**
     * Called before authentication; we block banned users here.
     */
    public function checkPreAuth(UserInterface $user): void
    {
        // Check if user email is verified
        if (method_exists($user, 'isVerified') && !$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte n\'a pas été vérifié. Veuillez vérifier votre email pour activer votre compte.'
            );
        }

        // If your User class has isBanned() and getBannedUntil(), use them
        if (method_exists($user, 'isBanned') && $user->isBanned()) {
            $bannedUntil = null;
            if (method_exists($user, 'getBannedUntil')) {
                $b = $user->getBannedUntil();
                $bannedUntil = $b ? $b->format('d/m/Y H:i') : null;
            }

            $message = 'Votre compte est banni' . ($bannedUntil ? ' jusqu\'au ' . $bannedUntil : '.');
            // This message will be shown as authentication error on login page
            throw new CustomUserMessageAccountStatusException($message);
        }
    }

    /**
     * Called after authentication; not used here.
     */
    public function checkPostAuth(UserInterface $user): void
    {
        // nothing
    }
}
