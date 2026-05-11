<?php

namespace App\Message;

final class GenerateMoodPdfMessage
{
    public function __construct(
        private int $moodId,
        private int $userId,
    ) {
    }

    public function getMoodId(): int
    {
        return $this->moodId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
