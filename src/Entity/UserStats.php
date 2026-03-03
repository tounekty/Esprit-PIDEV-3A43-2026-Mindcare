<?php

namespace App\Entity;

use App\Repository\UserStatsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserStatsRepository::class)]
class UserStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'stats', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $points = 0;

    #[ORM\Column(type: Types::JSON)]
    private array $badges = [];

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $totalEntries = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $consecutiveDays = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $lastEntryDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        if ($user->getStats() !== $this) {
            $user->setStats($this);
        }
        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function addPoints(int $amount): static
    {
        $this->points += $amount;
        return $this;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;
        return $this;
    }

    public function getBadges(): array
    {
        return $this->badges;
    }

    public function hasBadge(string $badge): bool
    {
        return in_array($badge, $this->badges);
    }

    public function addBadge(string $badge): static
    {
        if (!$this->hasBadge($badge)) {
            $this->badges[] = $badge;
        }
        return $this;
    }

    public function setBadges(array $badges): static
    {
        $this->badges = $badges;
        return $this;
    }

    public function getTotalEntries(): int
    {
        return $this->totalEntries;
    }

    public function setTotalEntries(int $count): static
    {
        $this->totalEntries = $count;
        return $this;
    }

    public function getConsecutiveDays(): int
    {
        return $this->consecutiveDays;
    }

    public function setConsecutiveDays(int $days): static
    {
        $this->consecutiveDays = $days;
        return $this;
    }

    public function getLastEntryDate(): ?\DateTime
    {
        return $this->lastEntryDate;
    }

    public function setLastEntryDate(?\DateTime $date): static
    {
        $this->lastEntryDate = $date;
        return $this;
    }
}
