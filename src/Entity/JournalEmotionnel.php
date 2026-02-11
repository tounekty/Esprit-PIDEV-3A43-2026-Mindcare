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

    #[ORM\Column]
    #[Assert\NotBlank(message: "La date est obligatoire.")]
    #[Assert\Type(type: "\DateTimeInterface", message: "La date doit être un objet DateTime valide.")]
    #[Assert\LessThanOrEqual("today", message: "La date ne peut pas être dans le futur.")]
    private ?\DateTime $dateecriture = null;

    #[ORM\ManyToOne(targetEntity: Mood::class, inversedBy: 'journals')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Assert\NotBlank(message: "L'humeur est obligatoire.")]
    private ?Mood $mood = null;

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

    public function getDateecriture(): ?\DateTime
    {
        return $this->dateecriture;
    }

    public function setDateecriture(\DateTime $dateecriture): static
    {
        $this->dateecriture = $dateecriture;

        return $this;
    }

    public function getMood(): ?mood
    {
        return $this->mood;
    }

    public function setMood(?mood $mood): static
    {
        $this->mood = $mood;

        return $this;
    }
}
