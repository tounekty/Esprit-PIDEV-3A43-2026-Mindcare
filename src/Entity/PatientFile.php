<?php

namespace App\Entity;

use App\Repository\PatientFileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PatientFileRepository::class)]
#[ORM\Table(name: 'patient_file')]
#[ORM\HasLifecycleCallbacks]
class PatientFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'patientFile', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $student = null;

    // Student-managed fields
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(min: 3, minMessage: "Veuillez fournir une description plus détaillée (au moins {{ limit }} caractères).")]
    private ?string $traitementsEnCours = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(min: 3, minMessage: "Veuillez fournir une description plus détaillée (au moins {{ limit }} caractères).")]
    private ?string $allergies = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(min: 2, minMessage: "Le nom doit comporter au moins {{ limit }} caractères.")]
    private ?string $contactUrgenceNom = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Assert\Regex(pattern: "/^\+?[0-9\s]{8,15}$/", message: "Veuillez entrer un numéro de téléphone valide.")]
    private ?string $contactUrgenceTel = null;

    // Clinical/Psy-only fields
    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(min: 5, minMessage: "Les antécédents doivent être plus détaillés.")]
    private ?string $antecedentsPersonnels = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(min: 5, minMessage: "Les antécédents doivent être plus détaillés.")]
    private ?string $antecedentsFamiliaux = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(min: 5, minMessage: "Veuillez préciser davantage le motif.")]
    private ?string $motifConsultation = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(min: 5, minMessage: "Veuillez préciser davantage les objectifs.")]
    private ?string $objectifsTherapeutiques = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notesGenerales = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $niveauRisque = 'Low';

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'patientFile', targetEntity: Appointment::class)]
    private Collection $appointments;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->appointments = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): self
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments[] = $appointment;
            $appointment->setPatientFile($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): self
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getPatientFile() === $this) {
                $appointment->setPatientFile(null);
            }
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(User $student): self
    {
        $this->student = $student;
        return $this;
    }

    public function getTraitementsEnCours(): ?string
    {
        return $this->traitementsEnCours;
    }

    public function setTraitementsEnCours(?string $traitementsEnCours): self
    {
        $this->traitementsEnCours = $traitementsEnCours;
        return $this;
    }

    public function getAllergies(): ?string
    {
        return $this->allergies;
    }

    public function setAllergies(?string $allergies): self
    {
        $this->allergies = $allergies;
        return $this;
    }

    public function getContactUrgenceNom(): ?string
    {
        return $this->contactUrgenceNom;
    }

    public function setContactUrgenceNom(?string $contactUrgenceNom): self
    {
        $this->contactUrgenceNom = $contactUrgenceNom;
        return $this;
    }

    public function getContactUrgenceTel(): ?string
    {
        return $this->contactUrgenceTel;
    }

    public function setContactUrgenceTel(?string $contactUrgenceTel): self
    {
        $this->contactUrgenceTel = $contactUrgenceTel;
        return $this;
    }

    public function getAntecedentsPersonnels(): ?string
    {
        return $this->antecedentsPersonnels;
    }

    public function setAntecedentsPersonnels(?string $antecedentsPersonnels): self
    {
        $this->antecedentsPersonnels = $antecedentsPersonnels;
        return $this;
    }

    public function getAntecedentsFamiliaux(): ?string
    {
        return $this->antecedentsFamiliaux;
    }

    public function setAntecedentsFamiliaux(?string $antecedentsFamiliaux): self
    {
        $this->antecedentsFamiliaux = $antecedentsFamiliaux;
        return $this;
    }

    public function getMotifConsultation(): ?string
    {
        return $this->motifConsultation;
    }

    public function setMotifConsultation(?string $motifConsultation): self
    {
        $this->motifConsultation = $motifConsultation;
        return $this;
    }

    public function getObjectifsTherapeutiques(): ?string
    {
        return $this->objectifsTherapeutiques;
    }

    public function setObjectifsTherapeutiques(?string $objectifsTherapeutiques): self
    {
        $this->objectifsTherapeutiques = $objectifsTherapeutiques;
        return $this;
    }

    public function getNotesGenerales(): ?string
    {
        return $this->notesGenerales;
    }

    public function setNotesGenerales(?string $notesGenerales): self
    {
        $this->notesGenerales = $notesGenerales;
        return $this;
    }

    public function getNiveauRisque(): ?string
    {
        return $this->niveauRisque;
    }

    public function setNiveauRisque(?string $niveauRisque): self
    {
        $this->niveauRisque = $niveauRisque;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
