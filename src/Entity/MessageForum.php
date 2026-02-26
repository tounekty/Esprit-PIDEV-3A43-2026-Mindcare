<?php

namespace App\Entity;

use App\Repository\MessageForumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_message_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?self $parentMessage = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(mappedBy: 'parentMessage', targetEntity: self::class)]
    private Collection $children;

    #[ORM\Column(name: 'is_anonymous', type: 'boolean')]
    private bool $isAnonymous = false;
    #[ORM\Column(name: 'attachment_path', type: 'string', length: 255, nullable: true)]
    private ?string $attachmentPath = null;

    #[ORM\Column(name: 'attachment_mime_type', type: 'string', length: 100, nullable: true)]
    private ?string $attachmentMimeType = null;

    #[ORM\Column(name: 'attachment_size', type: 'integer', nullable: true)]
    private ?int $attachmentSize = null;

    #[ORM\OneToOne(mappedBy: 'message', targetEntity: MessageForumAnalysis::class, cascade: ['persist', 'remove'])]
    private ?MessageForumAnalysis $analysis = null;

    public function __construct()
    {
        $this->dateMessage = new \DateTimeImmutable();
        $this->children = new ArrayCollection();
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

    public function getParentMessage(): ?self
    {
        return $this->parentMessage;
    }

    public function setParentMessage(?self $parentMessage): self
    {
        $this->parentMessage = $parentMessage;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParentMessage($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParentMessage() === $this) {
                $child->setParentMessage(null);
            }
        }

        return $this;
    }

    public function isAnonymous(): bool
    {
        return $this->isAnonymous;
    }

    public function setIsAnonymous(bool $isAnonymous): self
    {
        $this->isAnonymous = $isAnonymous;

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

    public function getAnalysis(): ?MessageForumAnalysis
    {
        return $this->analysis;
    }

    public function setAnalysis(?MessageForumAnalysis $analysis): self
    {
        if ($analysis !== null && $analysis->getMessage() !== $this) {
            $analysis->setMessage($this);
        }

        $this->analysis = $analysis;

        return $this;
    }
}
