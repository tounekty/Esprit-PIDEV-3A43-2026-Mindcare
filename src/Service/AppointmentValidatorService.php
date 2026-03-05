<?php

namespace App\Service;

use App\Entity\Appointment;

class AppointmentValidatorService
{
    /**
     * Règle 1 : Le lieu est obligatoire.
     * Règle 2 : L'étudiant est obligatoire.
     * Règle 3 : Le statut doit être parmi : pending, accepted, refused, cancelled.
     */
    public function validate(Appointment $appointment): bool
    {
        if (empty(trim($appointment->getLocation()))) {
            throw new \InvalidArgumentException('Le lieu est obligatoire.');
        }

        if ($appointment->getEtudiant() === null) {
            throw new \InvalidArgumentException('L\'étudiant est obligatoire.');
        }

        $allowedStatuses = ['pending', 'accepted', 'refused', 'cancelled'];
        if (!in_array($appointment->getStatus(), $allowedStatuses, true)) {
            throw new \InvalidArgumentException('Le statut est invalide.');
        }

        return true;
    }
}
