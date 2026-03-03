<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: "App\Repository\AppointmentRepository")]
#[ORM\Table(name: 'appointment')]
#[Vich\Uploadable]
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

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $zoomMeetingId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $zoomJoinUrl = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $zoomCreatedAt = null;

    #[Vich\UploadableField(mapping: 'appointment_reports', fileNameProperty: 'reportName')]
    private ?File $reportFile = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $reportName = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $reportUpdatedAt = null;

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

    public function getZoomMeetingId(): ?string
    {
        return $this->zoomMeetingId;
    }

    public function setZoomMeetingId(?string $zoomMeetingId): self
    {
        $this->zoomMeetingId = $zoomMeetingId;
        return $this;
    }

    public function getZoomJoinUrl(): ?string
    {
        return $this->zoomJoinUrl;
    }

    public function setZoomJoinUrl(?string $zoomJoinUrl): self
    {
        $this->zoomJoinUrl = $zoomJoinUrl;
        return $this;
    }

    public function getZoomCreatedAt(): ?\DateTimeInterface
    {
        return $this->zoomCreatedAt;
    }

    public function setZoomCreatedAt(?\DateTimeInterface $zoomCreatedAt): self
    {
        $this->zoomCreatedAt = $zoomCreatedAt;
        return $this;
    }

    public function setReportFile(?File $reportFile = null): self
    {
        $this->reportFile = $reportFile;

        if ($reportFile !== null) {
            $this->reportUpdatedAt = new \DateTime();
        }

        return $this;
    }

    public function getReportFile(): ?File
    {
        return $this->reportFile;
    }

    public function setReportName(?string $reportName): self
    {
        $this->reportName = $reportName;
        return $this;
    }

    public function getReportName(): ?string
    {
        return $this->reportName;
    }

    public function setReportUpdatedAt(?\DateTimeInterface $reportUpdatedAt): self
    {
        $this->reportUpdatedAt = $reportUpdatedAt;
        return $this;
    }

    public function getReportUpdatedAt(): ?\DateTimeInterface
    {
        return $this->reportUpdatedAt;
    }
}
