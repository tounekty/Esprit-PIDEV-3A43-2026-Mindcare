<?php

namespace App\Entity;

use App\Repository\JournalEmotionnelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: JournalEmotionnelRepository::class)]
class JournalEmotionnel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "Le contenu ne peut pas être vide.")]
    #[Assert\Length(
        min: 10,
        minMessage: "Le contenu doit comporter au moins {{ limit }} caractères."
    )]
    private ?string $contenu = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date est obligatoire.")]
    #[Assert\Type(type: "\\DateTimeInterface", message: "La date doit être un objet DateTime valide.")]
    private ?\DateTimeInterface $dateecriture = null;

    #[ORM\ManyToOne(targetEntity: Mood::class, inversedBy: 'journals')]
    #[ORM\JoinColumn(nullable: true, onDelete: "CASCADE")]
    private ?Mood $mood = null;

    
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfPath = null;

    public function __construct()
    {
        $this->dateecriture = new \DateTime();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getDateecriture(): ?\DateTimeInterface
    {
        return $this->dateecriture;
    }

    public function setDateecriture(\DateTimeInterface $dateecriture): static
    {
        $this->dateecriture = $dateecriture;

        return $this;
    }

    public function getMood(): ?Mood
    {
        return $this->mood;
    }

    public function setMood(?Mood $mood): static
    {
        $this->mood = $mood;

        return $this;
    }
    
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): static
    {
        $this->pdfPath = $pdfPath;

        return $this;
    }
}
