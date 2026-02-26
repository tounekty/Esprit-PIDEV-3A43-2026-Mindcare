<?php

namespace App\Message;

final class AnalyzeForumMessage
{
    public function __construct(
        private readonly int $messageId,
    ) {
    }

    public function getMessageId(): int
    {
        return $this->messageId;
    }
}
