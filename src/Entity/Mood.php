<?php

namespace App\Entity;

use App\Repository\MoodRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: MoodRepository::class)]
class Mood
{
#[ORM\OneToMany(mappedBy: 'mood', targetEntity: JournalEmotionnel::class, cascade: ['remove'])]
private Collection $journals;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

        #[ORM\Column(length: 50)]
        #[Assert\NotBlank(message: "L'humeur est obligatoire.")]
        #[Assert\Length(
                min: 2,
                max: 50,
                minMessage: "L'humeur doit comporter au moins {{ limit }} caractères.",
                maxMessage: "L'humeur ne peut pas dépasser {{ limit }} caractères."
        )]
        private ?string $humeur = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "L'intensité est obligatoire.")]
    #[Assert\Type(type: "integer", message: "L'intensité doit être un nombre entier.")]
    #[Assert\Range(
        min: 1,
        max: 5,
        notInRangeMessage: "L'intensité doit être entre {{ min }} et {{ max }}."
    )]
    private ?int $intensite = null;

    #[ORM\Column(type: "date")]
    #[Assert\NotBlank(message: "La date du mood est obligatoire.")]
    #[Assert\Type(type: "\DateTimeInterface", message: "La date doit être un objet DateTime valide.")]
    #[Assert\LessThanOrEqual("today", message: "La date ne peut pas être dans le futur.")]
    private ?\DateTime $datemood = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHumeur(): ?string
    {
        return $this->humeur;
    }

    public function setHumeur(string $humeur): static
    {
        $this->humeur = $humeur;

        return $this;
    }

    public function getIntensite(): ?int
    {
        return $this->intensite;
    }

    public function setIntensite(int $intensite): static
    {
        $this->intensite = $intensite;

        return $this;
    }

    public function getDatemood(): ?\DateTime
    {
        return $this->datemood;
    }

    public function setDatemood(?\DateTime $datemood): static
    {
        $this->datemood = $datemood;
        return $this;
    }

    public function getJournals(): Collection
    {
        return $this->journals;
    }

    public function addJournal(JournalEmotionnel $journal): static
    {
        if (!$this->journals->contains($journal)) {
            $this->journals->add($journal);
            $journal->setMood($this);
        }

        return $this;
    }

    public function removeJournal(JournalEmotionnel $journal): static
    {
        if ($this->journals->removeElement($journal)) {
            if ($journal->getMood() === $this) {
                $journal->setMood(null);
            }
        }

        return $this;
    }

    public function __construct()
    {
        $this->journals = new ArrayCollection();
    }
}
