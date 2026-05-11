<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserValidatorService;
use PHPUnit\Framework\TestCase;

class UserValidatorServiceTest extends TestCase
{
    private UserValidatorService $service;

    protected function setUp(): void
    {
        $this->service = new UserValidatorService();
    }

    // ─── Test 1 : utilisateur valide ─────────────────────────────────────────

    public function testValidUserPassesValidation(): void
    {
        $user = new User();
        $user->setFirstName('Ahmed');
        $user->setLastName('Ben Salem');
        $user->setEmail('ahmed@mindcare.com');
        $user->setPassword('SecurePass123');
        $user->setRole('etudiant');

        $this->assertTrue($this->service->validate($user));
    }

    // ─── Test 2 : prénom vide ────────────────────────────────────────────────

    public function testUserWithEmptyFirstNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom est obligatoire.');

        $user = new User();
        $user->setFirstName('');
        $user->setLastName('Ben Salem');
        $user->setEmail('ahmed@mindcare.com');
        $user->setPassword('SecurePass123');
        $user->setRole('etudiant');

        $this->service->validate($user);
    }

    // ─── Test 3 : email invalide ─────────────────────────────────────────────

    public function testUserWithInvalidEmailThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email est invalide.');

        $user = new User();
        $user->setFirstName('Ahmed');
        $user->setLastName('Ben Salem');
        $user->setEmail('ceci-nest-pas-un-email');
        $user->setPassword('SecurePass123');
        $user->setRole('etudiant');

        $this->service->validate($user);
    }

    // ─── Test 4 : mot de passe trop court ────────────────────────────────────

    public function testUserWithShortPasswordThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins 8 caractères.');

        $user = new User();
        $user->setFirstName('Ahmed');
        $user->setLastName('Ben Salem');
        $user->setEmail('ahmed@mindcare.com');
        $user->setPassword('abc');
        $user->setRole('etudiant');

        $this->service->validate($user);
    }

    // ─── Test 5 : rôle invalide ───────────────────────────────────────────────

    public function testUserWithInvalidRoleThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le rôle est invalide.');

        $user = new User();
        $user->setFirstName('Ahmed');
        $user->setLastName('Ben Salem');
        $user->setEmail('ahmed@mindcare.com');
        $user->setPassword('SecurePass123');

        // Manually set an invalid role bypassing the setter guard
        $reflection = new \ReflectionProperty(User::class, 'role');
        $reflection->setAccessible(true);
        $reflection->setValue($user, 'superadmin');

        $this->service->validate($user);
    }

    // ─── Test 6 : utilisateur banni ──────────────────────────────────────────

    public function testBannedUserThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cet utilisateur est banni et ne peut pas être validé.');

        $user = new User();
        $user->setFirstName('Ahmed');
        $user->setLastName('Ben Salem');
        $user->setEmail('ahmed@mindcare.com');
        $user->setPassword('SecurePass123');
        $user->setRole('etudiant');

        // Ban the user until tomorrow
        $user->ban(new \DateTime('+1 day'));

        $this->service->validate($user);
    }
}
