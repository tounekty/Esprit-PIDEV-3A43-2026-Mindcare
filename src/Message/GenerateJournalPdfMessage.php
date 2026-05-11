<?php

namespace App\Message;

final class GenerateJournalPdfMessage
{
    public function __construct(
        private int $journalId,
        private int $userId,
    ) {
    }

    public function getJournalId(): int
    {
        return $this->journalId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
