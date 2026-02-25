<?php

namespace App\Entity;

use App\Repository\ResourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
class Resource
{
    public const TYPE_ARTICLE = 'article';
    public const TYPE_VIDEO = 'video';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'Le titre ne doit pas depasser {{ limit }} caracteres.'
    )]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(
        min: 10,
        max: 5000,
        minMessage: 'La description doit contenir au moins {{ limit }} caracteres.',
        maxMessage: 'La description ne doit pas depasser {{ limit }} caracteres.'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le chemin du fichier ne doit pas depasser {{ limit }} caracteres.'
    )]
    private ?string $filePath = null;

    #[ORM\Column(length: 20, options: ['default' => self::TYPE_ARTICLE])]
    #[Assert\NotBlank(message: 'Le type de ressource est obligatoire.')]
    #[Assert\Choice(
        choices: [self::TYPE_ARTICLE, self::TYPE_VIDEO],
        message: 'Type de ressource invalide.'
    )]
    private string $type = self::TYPE_ARTICLE;

    #[ORM\Column(name: 'video_url', length: 500, nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'Le lien video ne doit pas depasser {{ limit }} caracteres.'
    )]
    #[Assert\Url(
        message: 'Le lien video doit etre une URL complete (ex: https://www.youtube.com/watch?v=XXXXXXXXXXX).'
    )]
    private ?string $videoUrl = null;

    #[ORM\Column(name: 'image_url', length: 500, nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'Le lien image ne doit pas depasser {{ limit }} caracteres.'
    )]
    #[Assert\Regex(
        pattern: '/^(https?:\/\/|\/uploads\/).+/',
        message: 'Le lien image doit etre une URL complete (ex: https://exemple.com/image.jpg).'
    )]
    private ?string $imageUrl = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\OneToMany(
        mappedBy: 'resource',
        targetEntity: Commentaire::class,
        orphanRemoval: true
    )]
    private Collection $commentaires;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
    }

    // ================= GETTERS / SETTERS =================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description);
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): self
    {
        $filePath = null !== $filePath ? trim($filePath) : null;
        $this->filePath = '' === $filePath ? null : $filePath;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = trim((string) $type);
        return $this;
    }

    public function getTypeLabel(): string
    {
        return $this->type === self::TYPE_VIDEO ? 'Video' : 'Article';
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): self
    {
        $videoUrl = null !== $videoUrl ? trim($videoUrl) : null;
        $this->videoUrl = '' === $videoUrl ? null : $videoUrl;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $imageUrl = null !== $imageUrl ? trim($imageUrl) : null;
        $this->imageUrl = '' === $imageUrl ? null : $imageUrl;
        return $this;
    }

    public function getYoutubeVideoId(): ?string
    {
        return $this->extractYoutubeVideoId($this->videoUrl);
    }

    public function getYoutubeEmbedUrl(): ?string
    {
        $videoId = $this->getYoutubeVideoId();
        if (!$videoId) {
            return null;
        }

        return sprintf('https://www.youtube.com/embed/%s', $videoId);
    }

    public function getYoutubeWatchUrl(): ?string
    {
        $videoId = $this->getYoutubeVideoId();
        if (!$videoId) {
            return null;
        }

        return sprintf('https://www.youtube.com/watch?v=%s', $videoId);
    }

    public function getYoutubeThumbnailUrl(): ?string
    {
        $videoId = $this->getYoutubeVideoId();
        if (!$videoId) {
            return null;
        }

        return sprintf('https://img.youtube.com/vi/%s/hqdefault.jpg', $videoId);
    }

    public function normalizeMediaFields(): self
    {
        if ($this->type === self::TYPE_VIDEO) {
            $this->imageUrl = null;
        }

        if ($this->type === self::TYPE_ARTICLE) {
            $this->videoUrl = null;
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
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
    
    #[Assert\Callback]
    public function validateMediaByType(ExecutionContextInterface $context): void
    {
        if ($this->filePath && !$this->isSafeLocalFileName($this->filePath)) {
            $context->buildViolation('Le fichier doit etre un nom de fichier simple (ex: support.pdf), sans /, \\ ou ..')
                ->atPath('filePath')
                ->addViolation();
        }

        if ($this->type === self::TYPE_VIDEO) {
            if (!$this->videoUrl) {
                $context->buildViolation('Pour le type Video YouTube, le lien video est obligatoire.')
                    ->atPath('videoUrl')
                    ->addViolation();
            } elseif (!$this->isYoutubeUrl($this->videoUrl)) {
                $context->buildViolation(
                    'URL YouTube invalide. Formats acceptes: https://www.youtube.com/watch?v=VIDEO_ID, https://youtu.be/VIDEO_ID, https://www.youtube.com/shorts/VIDEO_ID'
                )
                    ->atPath('videoUrl')
                    ->addViolation();
            }
        }

        if ($this->type === self::TYPE_ARTICLE && !$this->imageUrl && !$this->filePath) {
            $context->buildViolation(
                'Pour le type Article, ajoutez un lien image ou un fichier local (ex: support.pdf).'
            )
                ->atPath('imageUrl')
                ->addViolation();
        }
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setResource($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getResource() === $this) {
                $commentaire->setResource(null);
            }
        }

        return $this;
    }

    private function isYoutubeUrl(string $url): bool
    {
        return null !== $this->extractYoutubeVideoId($url);
    }

    private function isSafeLocalFileName(string $filePath): bool
    {
        if (str_contains($filePath, '..')) {
            return false;
        }

        return !str_contains($filePath, '/') && !str_contains($filePath, '\\');
    }

    private function extractYoutubeVideoId(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $pattern = '%(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})%';
        if (!preg_match($pattern, $url, $matches)) {
            return null;
        }

        return $matches[1] ?? null;
    }
}
