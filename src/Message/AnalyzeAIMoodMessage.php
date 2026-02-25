<?php

namespace App\Message;

final class AnalyzeAIMoodMessage
{
    public function __construct(
        private int $moodId,
        private string $humeur,
        private int $intensite,
        private ?string $description = null,
    ) {
    }

    public function getMoodId(): int
    {
        return $this->moodId;
    }

    public function getHumeur(): string
    {
        return $this->humeur;
    }

    public function getIntensite(): int
    {
        return $this->intensite;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
