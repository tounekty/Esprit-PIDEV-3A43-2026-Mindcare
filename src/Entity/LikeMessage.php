<?php

namespace App\Entity;

use App\Repository\LikeMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LikeMessageRepository::class)]
#[ORM\Table(name: 'like_message', indexes: [
    new ORM\Index(columns: ['message_id']),
])]
#[ORM\UniqueConstraint(name: 'uniq_like_user_message', columns: ['user_id', 'message_id'])]
class LikeMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: MessageForum::class)]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?MessageForum $message = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMessage(): ?MessageForum
    {
        return $this->message;
    }

    public function setMessage(?MessageForum $message): self
    {
        $this->message = $message;

        return $this;
    }
}
