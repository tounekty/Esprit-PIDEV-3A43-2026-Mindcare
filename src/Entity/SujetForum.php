<?php

namespace App\Entity;

use App\Repository\SujetForumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SujetForumRepository::class)]
#[ORM\Table(name: 'sujet_forum')]
class SujetForum
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $titre;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(name: 'date_creation', type: 'datetime_immutable')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(name: 'id_user', type: 'integer')]
    private int $idUser;

    #[ORM\Column(name: 'image_url', type: 'string', length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'is_pinned', type: 'boolean')]
    private bool $isPinned = false;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(name: 'attachment_path', type: 'string', length: 255, nullable: true)]
    private ?string $attachmentPath = null;

    #[ORM\Column(name: 'attachment_mime_type', type: 'string', length: 100, nullable: true)]
    private ?string $attachmentMimeType = null;

    #[ORM\Column(name: 'attachment_size', type: 'integer', nullable: true)]
    private ?int $attachmentSize = null;

    /**
     * @var Collection<int, MessageForum>
     */
    #[ORM\OneToMany(mappedBy: 'sujet', targetEntity: MessageForum::class, cascade: ['persist', 'remove'])]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getIdUser(): int
    {
        return $this->idUser;
    }

    public function setIdUser(int $idUser): self
    {
        $this->idUser = $idUser;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function setIsPinned(bool $isPinned): self
    {
        $this->isPinned = $isPinned;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

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

    /**
     * @return Collection<int, MessageForum>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(MessageForum $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setSujet($this);
        }

        return $this;
    }

    public function removeMessage(MessageForum $message): self
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getSujet() === $this) {
                $message->setSujet(null);
            }
        }

        return $this;
    }
}
