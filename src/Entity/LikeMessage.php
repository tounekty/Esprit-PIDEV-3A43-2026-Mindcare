<?php

namespace App\Entity;

use App\Repository\LikeMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LikeMessageRepository::class)]
#[ORM\Table(name: 'like_message')]
#[ORM\UniqueConstraint(name: 'uniq_like_user_message', columns: ['id_user', 'id_message'])]
class LikeMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_message', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
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
