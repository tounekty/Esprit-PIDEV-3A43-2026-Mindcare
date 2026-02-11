<?php

namespace App\Entity;

use App\Repository\SujetForumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SujetForumRepository::class)]
#[ORM\Table(name: 'sujet_forum')]
class SujetForum
{
    public const STATUS_VISIBLE = 'VISIBLE';
    public const STATUS_HIDDEN = 'HIDDEN';
    public const STATUS_REPORTED = 'REPORTED';
    public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
    public const STATUS_EDITED_BY_MODERATOR = 'EDITED_BY_MODERATOR';
    public const STATUS_DELETED = 'DELETED';

    public static function getStatusChoices(): array
    {
        return [
            'VISIBLE -> message visible par tous' => self::STATUS_VISIBLE,
            'HIDDEN -> masque temporairement' => self::STATUS_HIDDEN,
            'REPORTED -> signale (contenu choquant / dangereux)' => self::STATUS_REPORTED,
            'PENDING_APPROVAL -> en attente de validation' => self::STATUS_PENDING_APPROVAL,
            'EDITED_BY_MODERATOR -> modifie par un admin' => self::STATUS_EDITED_BY_MODERATOR,
            'DELETED -> supprime (soft delete)' => self::STATUS_DELETED,
        ];
    }

    public static function getStatusValues(): array
    {
        return array_values(self::getStatusChoices());
    }
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(
        min: 5,
        max: 120,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'Le titre ne peut pas depasser {{ limit }} caracteres.'
    )]
    private string $titre;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: 'La description doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'La description ne peut pas depasser {{ limit }} caracteres.'
    )]
    private string $description;

    #[ORM\Column(name: 'date_creation', type: 'datetime_immutable')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(name: 'id_user', type: 'integer')]
    #[Assert\NotBlank(message: "L'identifiant utilisateur est obligatoire.")]
    #[Assert\Positive(message: "L'identifiant utilisateur doit etre un nombre positif.")]
    private int $idUser;

    #[ORM\Column(name: 'image_url', type: 'string', length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'is_pinned', type: 'boolean')]
    private bool $isPinned = false;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(
        max: 30,
        maxMessage: 'Le statut ne peut pas depasser {{ limit }} caracteres.'
    )]
    private ?string $status = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(
        max: 60,
        maxMessage: 'La categorie ne peut pas depasser {{ limit }} caracteres.'
    )]
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
