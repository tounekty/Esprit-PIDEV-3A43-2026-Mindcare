<?php

namespace App\Tests\Service;

use App\Entity\Mood;
use App\Service\MoodValidatorService;
use PHPUnit\Framework\TestCase;

class MoodValidatorServiceTest extends TestCase
{
    private MoodValidatorService $service;

    protected function setUp(): void
    {
        $this->service = new MoodValidatorService();
    }

    // ─── Test 1 : mood valide ────────────────────────────────────────────────

    public function testValidMoodPassesValidation(): void
    {
        $mood = new Mood();
        $mood->setHumeur('heureux');
        $mood->setIntensite(4);
        $mood->setDatemood(new \DateTime('yesterday'));

        $this->assertTrue($this->service->validate($mood));
    }

    // ─── Test 2 : humeur vide ────────────────────────────────────────────────

    public function testMoodWithEmptyHumeurThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'humeur est obligatoire.');

        $mood = new Mood();
        $mood->setHumeur('');
        $mood->setIntensite(3);
        $mood->setDatemood(new \DateTime('yesterday'));

        $this->service->validate($mood);
    }

    // ─── Test 3 : intensité hors limites ─────────────────────────────────────

    public function testMoodWithIntensiteOutOfRangeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'intensité doit être comprise entre 1 et 5.');

        $mood = new Mood();
        $mood->setHumeur('triste');
        $mood->setIntensite(10);
        $mood->setDatemood(new \DateTime('yesterday'));

        $this->service->validate($mood);
    }

    // ─── Test 4 : date dans le futur ─────────────────────────────────────────

    public function testMoodWithFutureDateThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date du mood ne peut pas être dans le futur.');

        $mood = new Mood();
        $mood->setHumeur('heureux');
        $mood->setIntensite(3);
        $mood->setDatemood(new \DateTime('+2 days'));

        $this->service->validate($mood);
    }
}
