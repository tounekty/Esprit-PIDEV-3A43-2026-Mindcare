<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $firstName = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $lastName = '';

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email = '';

    #[ORM\Column(type: 'string', length: 50)]
    private string $role = 'etudiant';

    #[ORM\Column(type: 'string', length: 255)]
    #[Ignore]
    private string $password = '';

    #[ORM\OneToOne(mappedBy: 'student', targetEntity: PatientFile::class)]
    private ?PatientFile $patientFile = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: UserStats::class, cascade: ['persist'])]
    private ?UserStats $stats = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $bannedUntil = null;

    // ------------------- EMAIL VERIFICATION -------------------
    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    #[Ignore]
    private ?string $verificationToken = null;

    // ------------------- TIMESTAMPS -------------------
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

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

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $createdBy = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $updatedBy = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // ------------------- Getters & Setters -------------------

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): self { $this->firstName = $firstName; return $this; }

    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $lastName): self { $this->lastName = $lastName; return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getRole(): string { return strtolower($this->role); }
    public function setRole(string $role): self {
        $normalizedRole = strtolower(trim($role));
        $allowedRoles = ['etudiant', 'psychologue', 'admin'];
        if (!in_array($normalizedRole, $allowedRoles, true)) {
            throw new \InvalidArgumentException("Invalid role: $role");
        }
        $this->role = strtoupper($normalizedRole);
        return $this;
    }

    public function getPassword(): string { return (string) $this->password; }
    public function setPassword(#[\SensitiveParameter] string $password): self { $this->password = $password; return $this; }

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
    protected function setBannedUntil(?\DateTimeInterface $bannedUntil): self { $this->bannedUntil = $bannedUntil; return $this; }
    public function isBanned(): bool { return $this->bannedUntil !== null && $this->bannedUntil > new \DateTime(); }

    public function ban(\DateTimeInterface $until): self { $this->bannedUntil = $until; return $this; }
    public function unban(): self { $this->bannedUntil = null; return $this; }

    // ------------------- Forgot Password -------------------
    public function getResetCode(): ?string { return $this->resetCode; }
    public function setResetCode(?string $resetCode): self { $this->resetCode = $resetCode; return $this; }

    public function getResetCodeExpiresAt(): ?\DateTimeInterface { return $this->resetCodeExpiresAt; }
    protected function setResetCodeExpiresAt(?\DateTimeInterface $expiresAt): self { $this->resetCodeExpiresAt = $expiresAt; return $this; }

    public function initiatePasswordReset(string $code): self
    {
        $this->resetCode = $code;
        $this->resetCodeExpiresAt = new \DateTime('+15 minutes');
        return $this;
    }

    public function clearPasswordReset(): self
    {
        $this->resetCode = null;
        $this->resetCodeExpiresAt = null;
        return $this;
    }

    // ------------------- Face ID -------------------
    public function isFaceIdEnabled(): bool { return $this->faceIdEnabled; }
    public function setFaceIdEnabled(bool $enabled): self { $this->faceIdEnabled = $enabled; return $this; }

    public function getFaceIdSubject(): ?string { return $this->faceIdSubject; }
    public function setFaceIdSubject(?string $subject): self { $this->faceIdSubject = $subject; return $this; }

    // ------------------- Symfony Security -------------------
    public function getRoles(): array {
        return match (strtolower($this->role)) {
            'admin' => ['ROLE_ADMIN', 'ROLE_PSYCHOLOGUE', 'ROLE_ETUDIANT', 'ROLE_USER'],
            'psychologue' => ['ROLE_PSYCHOLOGUE', 'ROLE_ETUDIANT', 'ROLE_USER'],
            'etudiant' => ['ROLE_ETUDIANT', 'ROLE_USER'],
            default => ['ROLE_USER'],
        };
    }

    public function getUserIdentifier(): string { return (string) $this->email; }

    // ------------------- Email Verification -------------------
    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $verified): self { $this->isVerified = $verified; return $this; }

    public function getVerificationToken(): ?string { return $this->verificationToken; }
    public function setVerificationToken(#[\SensitiveParameter] ?string $token): self { $this->verificationToken = $token; return $this; }

    // ------------------- Timestamps -------------------
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function eraseCredentials() { /* Clear sensitive data */ }

    public function getCreatedBy(): ?self { return $this->createdBy; }
    public function setCreatedBy(?self $createdBy): self { $this->createdBy = $createdBy; return $this; }

    public function getUpdatedBy(): ?self { return $this->updatedBy; }
    public function setUpdatedBy(?self $updatedBy): self { $this->updatedBy = $updatedBy; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    protected function setUpdatedAt(\DateTimeImmutable $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
