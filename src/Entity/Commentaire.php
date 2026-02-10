<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Resource $resource = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom de l\'auteur est requis')]
    private ?string $authorName = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L\'email est requis')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas valide')]
    private ?string $authorEmail = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Le contenu du commentaire est requis')]
    private ?string $content = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 5,
        notInRangeMessage: 'La note doit être comprise entre {{ min }} et {{ max }}'
    )]
    private ?int $rating = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $editToken = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }




    public function getId(): ?int { return $this->id; }

    public function getResource(): ?Resource { return $this->resource; }
    public function setResource(?Resource $resource): self { 
        $this->resource = $resource; 
        return $this; 
    }

    public function getAuthorName(): ?string { return $this->authorName; }
    public function setAuthorName(string $authorName): self { 
        $this->authorName = $authorName; 
        return $this; 
    }

    public function getAuthorEmail(): ?string { return $this->authorEmail; }
    public function setAuthorEmail(string $authorEmail): self { 
        $this->authorEmail = $authorEmail; 
        return $this; 
    }

    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): self { 
        $this->content = $content; 
        return $this; 
    }

    public function getRating(): ?int { return $this->rating; }
    public function setRating(?int $rating): self { 
        $this->rating = $rating; 
        return $this; 
    }

    public function isApproved(): bool { return $this->approved; }
    public function setApproved(bool $approved): self { 
        $this->approved = $approved; 
        return $this; 
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { 
        $this->createdAt = $createdAt; 
        return $this; 
    }

    public function getEditToken(): ?string { return $this->editToken; }
    public function setEditToken(?string $editToken): self { 
        $this->editToken = $editToken; 
        return $this; 
    }
}