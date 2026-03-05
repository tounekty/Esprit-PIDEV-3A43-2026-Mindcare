<?php

namespace App\Tests\Service;

use App\Entity\SujetForum;
use App\Service\SujetForumValidatorService;
use PHPUnit\Framework\TestCase;

class SujetForumValidatorServiceTest extends TestCase
{
    private SujetForumValidatorService $service;

    protected function setUp(): void
    {
        $this->service = new SujetForumValidatorService();
    }

    // ─── Test 1 : sujet valide ────────────────────────────────────────────────

    public function testValidSujetForumPassesValidation(): void
    {
        $sujet = new SujetForum();
        $sujet->setTitre('Discussion Bien-être');
        $sujet->setDescription('Une discussion ouverte sur le bien-être des étudiants au quotidien.');

        $this->assertTrue($this->service->validate($sujet));
    }

    // ─── Test 2 : titre trop court ────────────────────────────────────────────

    public function testSujetForumWithShortTitreThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre doit contenir au moins 5 caractères.');

        $sujet = new SujetForum();
        $sujet->setTitre('Hi');
        $sujet->setDescription('Une description longue et valide pour ce test.');

        $this->service->validate($sujet);
    }

    // ─── Test 3 : description trop courte ─────────────────────────────────────

    public function testSujetForumWithShortDescriptionThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description doit contenir au moins 10 caractères.');

        $sujet = new SujetForum();
        $sujet->setTitre('Mon sujet important');
        $sujet->setDescription('Court');

        $this->service->validate($sujet);
    }

    // ─── Test 4 : statut invalide ─────────────────────────────────────────────

    public function testSujetForumWithInvalidStatusThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut du sujet est invalide.');

        $sujet = new SujetForum();
        $sujet->setTitre('Mon sujet important');
        $sujet->setDescription('Une description longue et valide pour ce sujet de forum.');

        // Force an invalid status via reflection
        $reflection = new \ReflectionProperty(SujetForum::class, 'status');
        $reflection->setAccessible(true);
        $reflection->setValue($sujet, 'INVALID');

        $this->service->validate($sujet);
    }
}
