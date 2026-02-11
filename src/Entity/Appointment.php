<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\AppointmentRepository")]
#[ORM\Table(name: 'appointment')]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull(message: "Veuillez sélectionner une date.")]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire.")]
    private ?string $location = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $status = 'pending';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'idetudiant', referencedColumnName: 'id')]
    #[Assert\NotNull(message: "L'étudiant est obligatoire.")]
    private ?User $etudiant = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'idpsy', referencedColumnName: 'id')]
    #[Assert\NotNull(message: "Le psychologue est obligatoire.")]
    private ?User $psychologue = null;

    #[ORM\ManyToOne(targetEntity: PatientFile::class, inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?PatientFile $patientFile = null;

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getEtudiant(): ?User
    {
        return $this->etudiant;
    }

    public function setEtudiant(?User $etudiant): self
    {
        $this->etudiant = $etudiant;
        return $this;
    }

    public function getPsychologue(): ?User
    {
        return $this->psychologue;
    }

    public function setPsychologue(?User $psychologue): self
    {
        $this->psychologue = $psychologue;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPatientFile(): ?PatientFile
    {
        return $this->patientFile;
    }

    public function setPatientFile(?PatientFile $patientFile): self
    {
        $this->patientFile = $patientFile;
        return $this;
    }
}
