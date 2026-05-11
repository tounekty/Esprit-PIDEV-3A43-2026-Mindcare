<?php

namespace App\Tests\Service;

use App\Entity\Event;
use App\Service\EventValidatorService;
use PHPUnit\Framework\TestCase;

class EventValidatorServiceTest extends TestCase
{
    private EventValidatorService $service;

    protected function setUp(): void
    {
        $this->service = new EventValidatorService();
    }

    // ─── Test 1 : événement valide ───────────────────────────────────────────

    public function testValidEventPassesValidation(): void
    {
        $event = new Event();
        $event->setTitre('Conférence Santé Mentale');
        $event->setDescription('Une conférence sur la santé mentale des étudiants.');
        $event->setLieu('Amphithéâtre A');
        $event->setCapacite(100);
        $event->setDateEvent(new \DateTime('+7 days'));

        $this->assertTrue($this->service->validate($event));
    }

    // ─── Test 2 : titre vide ─────────────────────────────────────────────────

    public function testEventWithEmptyTitreThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire.');

        $event = new Event();
        $event->setTitre('');
        $event->setDescription('Description valide de l\'événement.');
        $event->setLieu('Salle B');
        $event->setCapacite(50);
        $event->setDateEvent(new \DateTime('+7 days'));

        $this->service->validate($event);
    }

    // ─── Test 3 : capacité nulle ou négative ─────────────────────────────────

    public function testEventWithZeroCapaciteThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacité doit être strictement positive.');

        $event = new Event();
        $event->setTitre('Atelier Bien-être');
        $event->setDescription('Description valide de l\'événement.');
        $event->setLieu('Salle B');
        $event->setCapacite(0);
        $event->setDateEvent(new \DateTime('+7 days'));

        $this->service->validate($event);
    }

    // ─── Test 4 : date dans le passé ─────────────────────────────────────────

    public function testEventWithPastDateThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de l\'événement doit être dans le futur.');

        $event = new Event();
        $event->setTitre('Atelier Bien-être');
        $event->setDescription('Description valide de l\'événement.');
        $event->setLieu('Salle B');
        $event->setCapacite(30);
        $event->setDateEvent(new \DateTime('-1 day'));

        $this->service->validate($event);
    }
}
