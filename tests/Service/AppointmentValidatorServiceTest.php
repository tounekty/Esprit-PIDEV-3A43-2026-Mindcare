<?php

namespace App\Tests\Service;

use App\Entity\Appointment;
use App\Entity\User;
use App\Service\AppointmentValidatorService;
use PHPUnit\Framework\TestCase;

class AppointmentValidatorServiceTest extends TestCase
{
    private AppointmentValidatorService $service;

    protected function setUp(): void
    {
        $this->service = new AppointmentValidatorService();
    }

    // ─── Test 1 : rendez-vous valide ─────────────────────────────────────────

    public function testValidAppointmentPassesValidation(): void
    {
        $student = new User();
        $student->setFirstName('Ahmed');
        $student->setLastName('Ben Salem');
        $student->setEmail('ahmed@mindcare.com');
        $student->setPassword('password123');

        $appointment = new Appointment();
        $appointment->setLocation('Cabinet 12');
        $appointment->setEtudiant($student);
        $appointment->setStatus('pending');

        $this->assertTrue($this->service->validate($appointment));
    }

    // ─── Test 2 : lieu vide ──────────────────────────────────────────────────

    public function testAppointmentWithEmptyLocationThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le lieu est obligatoire.');

        $student = new User();
        $student->setFirstName('Ahmed');
        $student->setLastName('Ben Salem');
        $student->setEmail('ahmed@mindcare.com');
        $student->setPassword('password123');

        $appointment = new Appointment();
        $appointment->setLocation('');
        $appointment->setEtudiant($student);
        $appointment->setStatus('pending');

        $this->service->validate($appointment);
    }

    // ─── Test 3 : étudiant manquant ──────────────────────────────────────────

    public function testAppointmentWithoutEtudiantThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'étudiant est obligatoire.');

        $appointment = new Appointment();
        $appointment->setLocation('Cabinet 12');
        $appointment->setStatus('pending');
        // no etudiant set

        $this->service->validate($appointment);
    }

    // ─── Test 4 : statut invalide ─────────────────────────────────────────────

    public function testAppointmentWithInvalidStatusThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut est invalide.');

        $student = new User();
        $student->setFirstName('Ahmed');
        $student->setLastName('Ben Salem');
        $student->setEmail('ahmed@mindcare.com');
        $student->setPassword('password123');

        $appointment = new Appointment();
        $appointment->setLocation('Cabinet 12');
        $appointment->setEtudiant($student);
        $appointment->setStatus('unknown_status');

        $this->service->validate($appointment);
    }
}
