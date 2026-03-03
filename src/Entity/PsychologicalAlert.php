<?php

namespace App\Entity;

use App\Repository\PsychologicalAlertRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PsychologicalAlertRepository::class)]
#[ORM\Table(name: 'psychological_alert')]
final class PsychologicalAlert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $alertType; // 'consecutive_negative_moods' or 'dangerous_keywords'

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $notifiedAdmin = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $notifiedPsychologist = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $resolved = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resolvedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNotes = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getAlertType(): string
    {
        return $this->alertType;
    }

    public function setAlertType(string $alertType): self
    {
        $this->alertType = $alertType;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function isNotifiedAdmin(): bool
    {
        return $this->notifiedAdmin;
    }

    public function setNotifiedAdmin(bool $notifiedAdmin): self
    {
        $this->notifiedAdmin = $notifiedAdmin;
        return $this;
    }

    public function isNotifiedPsychologist(): bool
    {
        return $this->notifiedPsychologist;
    }

    public function setNotifiedPsychologist(bool $notifiedPsychologist): self
    {
        $this->notifiedPsychologist = $notifiedPsychologist;
        return $this;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function setResolved(bool $resolved): self
    {
        $this->resolved = $resolved;
        if ($resolved) {
            $this->resolvedAt = new \DateTime();
        }
        return $this;
    }

    public function getResolvedAt(): ?\DateTimeInterface
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeInterface $resolvedAt): self
    {
        $this->resolvedAt = $resolvedAt;
        return $this;
    }

    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    public function setAdminNotes(?string $adminNotes): self
    {
        $this->adminNotes = $adminNotes;
        return $this;
    }
}
