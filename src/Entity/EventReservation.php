<?php

namespace App\Entity;

use App\Repository\EventReservationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventReservationRepository::class)]
#[ORM\Table(name: 'reservation_event', indexes: [
    new ORM\Index(columns: ['event_id']),
    new ORM\Index(columns: ['user_id']),
])]
class EventReservation
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REFUSED = 'refused';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', name: 'date_reservation')]
    #[Assert\NotNull(message: 'La date de reservation est obligatoire.')]
    private \DateTimeInterface $dateReservation;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    private string $statut = self::STATUS_PENDING;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'evenement est obligatoire.')]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'L\'utilisateur est obligatoire.')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    private string $nom = '';

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le prenom est obligatoire.')]
    private string $prenom = '';

    #[ORM\Column(type: 'string', length: 30)]
    #[Assert\NotBlank(message: 'Le telephone est obligatoire.')]
    private string $telephone = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    #[Ignore]
    private ?string $confirmationToken = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $smsReminderSent = false;

    public function __construct()
    {
        $this->dateReservation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateReservation(): \DateTimeInterface
    {
        return $this->dateReservation;
    }

    protected function setDateReservation(\DateTimeInterface $dateReservation): self
    {
        $this->dateReservation = $dateReservation;
        return $this;
    }

    public function reserveNow(): self
    {
        $this->dateReservation = new \DateTime();
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;
        return $this;
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

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(#[\SensitiveParameter] ?string $confirmationToken): self
    {
        $this->confirmationToken = $confirmationToken;
        return $this;
    }

    public function isSmsReminderSent(): bool
    {
        return $this->smsReminderSent;
    }

    public function setSmsReminderSent(bool $smsReminderSent): self
    {
        $this->smsReminderSent = $smsReminderSent;
        return $this;
    }
}

