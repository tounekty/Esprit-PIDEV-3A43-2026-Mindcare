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
    private string $role = 'etudiant';

    #[ORM\Column(type: 'string')]
    private ?string $password = null;

    #[ORM\OneToOne(mappedBy: 'student', targetEntity: PatientFile::class)]
    private ?PatientFile $patientFile = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: UserStats::class, cascade: ['persist', 'remove'])]
    private ?UserStats $stats = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $bannedUntil = null;

    // ------------------- EMAIL VERIFICATION -------------------
    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $verificationToken = null;

    // ------------------- TIMESTAMPS -------------------
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    // ------------------- FORGOT PASSWORD -------------------
    #[ORM\Column(type: 'string', length: 6, nullable: true)]
    private ?string $resetCode = null;

 #[ORM\Column(type: 'datetime', nullable: true)]
private ?\DateTimeInterface $resetCodeExpiresAt = null;

    // ------------------- FACE ID -------------------
    #[ORM\Column(type: 'boolean')]
    private bool $faceIdEnabled = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $faceIdSubject = null;

    // ------------------- Getters & Setters -------------------

    public function getId(): ?int { return $this->id; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $firstName): self { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $lastName): self { $this->lastName = $lastName; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): self {
        $allowedRoles = ['etudiant', 'psychologue', 'admin'];
        if (!in_array($role, $allowedRoles, true)) {
            throw new \InvalidArgumentException("Invalid role: $role");
        }
        $this->role = $role;
        return $this;
    }

    public function getPassword(): string { return (string) $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function getPatientFile(): ?PatientFile { return $this->patientFile; }
    public function setPatientFile(?PatientFile $patientFile): self { $this->patientFile = $patientFile; return $this; }

    public function getStats(): ?UserStats { return $this->stats; }
    public function setStats(?UserStats $stats): self {
        if ($stats !== null && $stats->getUser() !== $this) {
            $stats->setUser($this);
        }
        $this->stats = $stats;
        return $this;
    }

    public function getBannedUntil(): ?\DateTimeInterface { return $this->bannedUntil; }
    public function setBannedUntil(?\DateTimeInterface $bannedUntil): self { $this->bannedUntil = $bannedUntil; return $this; }
    public function isBanned(): bool { return $this->bannedUntil !== null && $this->bannedUntil > new \DateTime(); }

    // ------------------- Forgot Password -------------------
    public function getResetCode(): ?string { return $this->resetCode; }
    public function setResetCode(?string $resetCode): self { $this->resetCode = $resetCode; return $this; }

    public function getResetCodeExpiresAt(): ?\DateTimeInterface { return $this->resetCodeExpiresAt; }
    public function setResetCodeExpiresAt(?\DateTimeInterface $expiresAt): self { $this->resetCodeExpiresAt = $expiresAt; return $this; }

    // ------------------- Face ID -------------------
    public function isFaceIdEnabled(): bool { return $this->faceIdEnabled; }
    public function setFaceIdEnabled(bool $enabled): self { $this->faceIdEnabled = $enabled; return $this; }

    public function getFaceIdSubject(): ?string { return $this->faceIdSubject; }
    public function setFaceIdSubject(?string $subject): self { $this->faceIdSubject = $subject; return $this; }

    // ------------------- Symfony Security -------------------
    public function getRoles(): array {
        return match ($this->role) {
            'admin' => ['ROLE_ADMIN'],
            'psychologue' => ['ROLE_PSYCHOLOGUE'],
            'etudiant' => ['ROLE_ETUDIANT'],
            default => ['ROLE_USER'],
        };
    }

    public function getUserIdentifier(): string { return (string) $this->email; }

    // ------------------- Email Verification -------------------
    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $verified): self { $this->isVerified = $verified; return $this; }

    public function getVerificationToken(): ?string { return $this->verificationToken; }
    public function setVerificationToken(?string $token): self { $this->verificationToken = $token; return $this; }

    // ------------------- Timestamps -------------------
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function eraseCredentials() { /* Clear sensitive data */ }
}
