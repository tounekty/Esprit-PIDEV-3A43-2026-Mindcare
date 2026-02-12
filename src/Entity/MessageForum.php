<?php

namespace App\Entity;

use App\Repository\MessageForumRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageForumRepository::class)]
#[ORM\Table(name: 'message_forum')]
class MessageForum
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Le message est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 1000,
        minMessage: 'Le message doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'Le message ne peut pas depasser {{ limit }} caracteres.'
    )]
    private string $contenu;

    #[ORM\Column(name: 'date_message', type: 'datetime_immutable')]
    private \DateTimeImmutable $dateMessage;

    #[ORM\ManyToOne(targetEntity: SujetForum::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(name: 'id_sujet', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?SujetForum $sujet = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;
    #[ORM\Column(name: 'attachment_path', type: 'string', length: 255, nullable: true)]
    private ?string $attachmentPath = null;

    #[ORM\Column(name: 'attachment_mime_type', type: 'string', length: 100, nullable: true)]
    private ?string $attachmentMimeType = null;

    #[ORM\Column(name: 'attachment_size', type: 'integer', nullable: true)]
    private ?int $attachmentSize = null;

    public function __construct()
    {
        $this->dateMessage = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getDateMessage(): \DateTimeImmutable
    {
        return $this->dateMessage;
    }

    public function setDateMessage(\DateTimeImmutable $dateMessage): self
    {
        $this->dateMessage = $dateMessage;

        return $this;
    }

    public function getSujet(): ?SujetForum
    {
        return $this->sujet;
    }

    public function setSujet(?SujetForum $sujet): self
    {
        $this->sujet = $sujet;

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

    public function getAttachmentPath(): ?string
    {
        return $this->attachmentPath;
    }

    public function setAttachmentPath(?string $attachmentPath): self
    {
        $this->attachmentPath = $attachmentPath;

        return $this;
    }

    public function getAttachmentMimeType(): ?string
    {
        return $this->attachmentMimeType;
    }

    public function setAttachmentMimeType(?string $attachmentMimeType): self
    {
        $this->attachmentMimeType = $attachmentMimeType;

        return $this;
    }

    public function getAttachmentSize(): ?int
    {
        return $this->attachmentSize;
    }

    public function setAttachmentSize(?int $attachmentSize): self
    {
        $this->attachmentSize = $attachmentSize;

        return $this;
    }
}
