<?php

namespace App\Service;

use App\Entity\User;

class UserValidatorService
{
    /**
     * Règle 1 : Le prénom est obligatoire.
     * Règle 2 : Le nom est obligatoire.
     * Règle 3 : L'email doit être valide.
     * Règle 4 : Le mot de passe doit contenir au moins 8 caractères.
     * Règle 5 : Le rôle doit être parmi : etudiant, psychologue, admin.
     * Règle 6 : Un utilisateur banni ne peut pas être validé.
     */
    public function validate(User $user): bool
    {
        if (empty(trim($user->getFirstName()))) {
            throw new \InvalidArgumentException('Le prénom est obligatoire.');
        }

        if (empty(trim($user->getLastName()))) {
            throw new \InvalidArgumentException('Le nom est obligatoire.');
        }

        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'email est invalide.');
        }

        if (strlen($user->getPassword()) < 8) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        $rolesAutorisés = ['etudiant', 'psychologue', 'admin'];
        if (!in_array($user->getRole(), $rolesAutorisés, true)) {
            throw new \InvalidArgumentException('Le rôle est invalide.');
        }

        if ($user->getBannedUntil() !== null && $user->getBannedUntil() > new \DateTime()) {
            throw new \InvalidArgumentException('Cet utilisateur est banni et ne peut pas être validé.');
        }

        return true;
    }
}
