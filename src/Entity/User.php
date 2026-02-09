<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: "App\Repository\UserRepository")]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $role = 'etudiant'; // default role

    #[ORM\Column(type: 'string')]
    private ?string $password = null;

    // -------------------- Ban logic --------------------
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $bannedUntil = null;

    public function getBannedUntil(): ?\DateTimeInterface
    {
        return $this->bannedUntil;
    }

    public function setBannedUntil(?\DateTimeInterface $bannedUntil): self
    {
        $this->bannedUntil = $bannedUntil;
        return $this;
    }

    // Returns true if the user is currently banned
    public function isBanned(): bool
    {
        return $this->bannedUntil !== null && $this->bannedUntil > new \DateTime();
    }

    // -------------------- Getters & Setters --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $allowedRoles = ['etudiant', 'psychologue', 'admin'];
        if (!in_array($role, $allowedRoles)) {
            throw new \InvalidArgumentException("Invalid role: $role");
        }
        $this->role = $role;
        return $this;
    }

    // -------------------- Symfony Security --------------------

    public function getRoles(): array
    {
        $roleMap = [
            'etudiant' => 'ROLE_ETUDIANT',
            'psychologue' => 'ROLE_PSYCHOLOGUE',
            'admin' => 'ROLE_ADMIN',
        ];

        return [$roleMap[$this->role] ?? 'ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials()
    {
        // Clear temporary sensitive data if any
    }
}
