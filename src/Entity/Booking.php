<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'étudiant est obligatoire.")]
    #[Assert\Length(min: 2, minMessage: "Le nom doit contenir au moins {{ limit }} caractères.")]
    private ?string $nomEtudiant = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'adresse email est obligatoire.")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas une adresse valide.")]
    private ?string $emailEtudiant = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateReservation = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Veuillez sélectionner un événement.")]
    private ?Event $event = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomEtudiant(): ?string
    {
        return $this->nomEtudiant;
    }

    public function setNomEtudiant(string $nomEtudiant): static
    {
        $this->nomEtudiant = $nomEtudiant;
        return $this;
    }

    public function getEmailEtudiant(): ?string
    {
        return $this->emailEtudiant;
    }

    public function setEmailEtudiant(string $emailEtudiant): static
    {
        $this->emailEtudiant = $emailEtudiant;
        return $this;
    }

    public function getDateReservation(): ?\DateTimeImmutable
    {
        return $this->dateReservation;
    }

    public function setDateReservation(\DateTimeImmutable $dateReservation): static
    {
        $this->dateReservation = $dateReservation;
        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
    }
}
