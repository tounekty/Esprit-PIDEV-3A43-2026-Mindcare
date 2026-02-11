<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
<<<<<<< HEAD
=======
#[ORM\Table(name: 'event')]
>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
<<<<<<< HEAD
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre ne peut pas être vide")]
    private ?string $titre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La date est obligatoire")]
    #[Assert\GreaterThan("today", message: "La date doit être dans le futur")]
    private ?\DateTimeImmutable $dateHeure = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire")]
    private ?string $lieu = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La capacité est obligatoire")]
    #[Assert\Positive(message: "La capacité doit être un nombre positif")]
    private ?int $capaciteMax = null;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'event', orphanRemoval: true)]
    private Collection $bookings;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    public function __toString(): string 
    { 
        return $this->titre ?? ''; 
=======
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime', name: 'date_event')]
    #[Assert\NotNull(message: 'La date est obligatoire.')]
    #[Assert\GreaterThan('now', message: 'La date doit etre dans le futur.')]
    private ?\DateTimeInterface $dateEvent = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le lieu est obligatoire.')]
    private ?string $lieu = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'La capacite est obligatoire.')]
    #[Assert\Positive(message: 'La capacite doit etre positive.')]
    private ?int $capacite = null;

    /**
     * @var Collection<int, EventReservation>
     */
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventReservation::class, orphanRemoval: true)]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

<<<<<<< HEAD
    public function setTitre(string $titre): static
=======
    public function setTitre(string $titre): self
>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
<<<<<<< HEAD
    public function setDescription(?string $description): static
=======

    public function setDescription(string $description): self
>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
    {
        $this->description = $description;
        return $this;
    }

<<<<<<< HEAD
    public function getDateHeure(): ?\DateTimeImmutable
    {
        return $this->dateHeure;
    }

    public function setDateHeure(\DateTimeImmutable $dateHeure): static
    {
        $this->dateHeure = $dateHeure;
=======
    public function getDateEvent(): ?\DateTimeInterface
    {
        return $this->dateEvent;
    }

    public function setDateEvent(?\DateTimeInterface $dateEvent): self
    {
        $this->dateEvent = $dateEvent;
>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

<<<<<<< HEAD
    public function setLieu(string $lieu): static
=======
    public function setLieu(string $lieu): self
>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
    {
        $this->lieu = $lieu;
        return $this;
    }

<<<<<<< HEAD
    public function getCapaciteMax(): ?int
    {
        return $this->capaciteMax;
    }

    public function setCapaciteMax(int $capaciteMax): static
    {
        $this->capaciteMax = $capaciteMax;
=======
    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(int $capacite): self
    {
        $this->capacite = $capacite;
>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
        return $this;
    }

    /**
<<<<<<< HEAD
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setEvent($this);
        }
        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            if ($booking->getEvent() === $this) {
                $booking->setEvent(null);
            }
        }
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }
}
=======
     * @return Collection<int, EventReservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(EventReservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setEvent($this);
        }

        return $this;
    }

    public function removeReservation(EventReservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getEvent() === $this) {
                $reservation->setEvent(null);
            }
        }

        return $this;
    }
}
>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
