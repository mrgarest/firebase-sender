<?php

namespace Garest\FirebaseSender\DTO;

class SendReport
{
    /**
     * @param MessageResult[] $messages
     */
    public function __construct(
        public int $totalCount,
        public int $successCount,
        public int $failureCount,
        public array $messages
    ) {}
}
