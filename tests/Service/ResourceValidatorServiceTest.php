<?php

namespace App\Tests\Service;

use App\Entity\Resource;
use App\Service\ResourceValidatorService;
use PHPUnit\Framework\TestCase;

class ResourceValidatorServiceTest extends TestCase
{
    private ResourceValidatorService $service;

    protected function setUp(): void
    {
        $this->service = new ResourceValidatorService();
    }

    // ─── Test 1 : ressource valide ───────────────────────────────────────────

    public function testValidResourcePassesValidation(): void
    {
        $resource = new Resource();
        $resource->setTitle('Gestion du stress');
        $resource->setDescription('Un article complet sur la gestion du stress chez les étudiants.');
        $resource->setType(Resource::TYPE_ARTICLE);

        $this->assertTrue($this->service->validate($resource));
    }

    // ─── Test 2 : titre trop court ───────────────────────────────────────────

    public function testResourceWithShortTitleThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre doit contenir au moins 3 caractères.');

        $resource = new Resource();
        $resource->setTitle('AB');
        $resource->setDescription('Description suffisamment longue pour passer la validation.');
        $resource->setType(Resource::TYPE_ARTICLE);

        $this->service->validate($resource);
    }

    // ─── Test 3 : description trop courte ────────────────────────────────────

    public function testResourceWithShortDescriptionThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description doit contenir au moins 10 caractères.');

        $resource = new Resource();
        $resource->setTitle('Mon article');
        $resource->setDescription('Court');
        $resource->setType(Resource::TYPE_ARTICLE);

        $this->service->validate($resource);
    }

    // ─── Test 4 : type invalide ───────────────────────────────────────────────

    public function testResourceWithInvalidTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type doit être article ou video.');

        $resource = new Resource();
        $resource->setTitle('Mon article');
        $resource->setDescription('Description suffisamment longue pour passer la validation.');

        // Force an invalid type via reflection
        $reflection = new \ReflectionProperty(Resource::class, 'type');
        $reflection->setAccessible(true);
        $reflection->setValue($resource, 'podcast');

        $this->service->validate($resource);
    }
}
