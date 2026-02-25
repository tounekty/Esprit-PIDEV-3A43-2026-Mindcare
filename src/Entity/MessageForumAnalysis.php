<?php

namespace App\Entity;

use App\Repository\MessageForumAnalysisRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageForumAnalysisRepository::class)]
#[ORM\Table(name: 'message_forum_analysis')]
class MessageForumAnalysis
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'analysis', targetEntity: MessageForum::class)]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE', unique: true)]
    private ?MessageForum $message = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'sentiment_label', type: 'string', length: 30, nullable: true)]
    private ?string $sentimentLabel = null;

    #[ORM\Column(name: 'sentiment_score', type: 'float', nullable: true)]
    private ?float $sentimentScore = null;

    #[ORM\Column(name: 'urgency_label', type: 'string', length: 30, nullable: true)]
    private ?string $urgencyLabel = null;

    #[ORM\Column(name: 'urgency_score', type: 'float', nullable: true)]
    private ?float $urgencyScore = null;

    #[ORM\Column(name: 'is_urgent', type: 'boolean')]
    private bool $isUrgent = false;

    #[ORM\Column(name: 'model_name', type: 'string', length: 180, nullable: true)]
    private ?string $modelName = null;

    #[ORM\Column(name: 'raw_response', type: 'text', nullable: true)]
    private ?string $rawResponse = null;

    #[ORM\Column(name: 'error_message', type: 'string', length: 500, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(name: 'analyzed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $analyzedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?MessageForum
    {
        return $this->message;
    }

    public function setMessage(MessageForum $message): self
    {
        $this->message = $message;
        if ($message->getAnalysis() !== $this) {
            $message->setAnalysis($this);
        }

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSentimentLabel(): ?string
    {
        return $this->sentimentLabel;
    }

    public function setSentimentLabel(?string $sentimentLabel): self
    {
        $this->sentimentLabel = $sentimentLabel;

        return $this;
    }

    public function getSentimentScore(): ?float
    {
        return $this->sentimentScore;
    }

    public function setSentimentScore(?float $sentimentScore): self
    {
        $this->sentimentScore = $sentimentScore;

        return $this;
    }

    public function getUrgencyLabel(): ?string
    {
        return $this->urgencyLabel;
    }

    public function setUrgencyLabel(?string $urgencyLabel): self
    {
        $this->urgencyLabel = $urgencyLabel;

        return $this;
    }

    public function getUrgencyScore(): ?float
    {
        return $this->urgencyScore;
    }

    public function setUrgencyScore(?float $urgencyScore): self
    {
        $this->urgencyScore = $urgencyScore;

        return $this;
    }

    public function isUrgent(): bool
    {
        return $this->isUrgent;
    }

    public function setIsUrgent(bool $isUrgent): self
    {
        $this->isUrgent = $isUrgent;

        return $this;
    }

    public function getModelName(): ?string
    {
        return $this->modelName;
    }

    public function setModelName(?string $modelName): self
    {
        $this->modelName = $modelName;

        return $this;
    }

    public function getRawResponse(): ?string
    {
        return $this->rawResponse;
    }

    public function setRawResponse(?string $rawResponse): self
    {
        $this->rawResponse = $rawResponse;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getAnalyzedAt(): ?\DateTimeImmutable
    {
        return $this->analyzedAt;
    }

    public function setAnalyzedAt(?\DateTimeImmutable $analyzedAt): self
    {
        $this->analyzedAt = $analyzedAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
