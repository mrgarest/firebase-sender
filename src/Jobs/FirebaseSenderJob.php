<?php

namespace MrGarest\FirebaseSender\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use MrGarest\FirebaseSender\FirebaseSender;
use MrGarest\FirebaseSender\Models\FirebaseSenderLog;
use MrGarest\FirebaseSender\Utils;

class FirebaseSenderJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $serviceAccount,
        public array $messages,
        public array|null $ulids,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $firebaseSender = new FirebaseSender($this->serviceAccount);
        $firebaseSender->logEnabled(false);
        $firebaseSender->setMessages($this->messages);
        $results = $firebaseSender->send();

        if (empty($this->ulids)) return;

        $updateData = [];
        foreach ($results->messages as $index => $message) {
            $ulid = $this->ulids[$index] ?? null;
            if (!$ulid) continue;

            $updateData[] = [
                'ulid' => $ulid,
                'message_id' => $message->success ? $message->messageId : null,
                'sent_at' => $message->success ? $message->datetime : null,
                'failed_at' => !$message->success ? $message->datetime : null,
                'exception' => Utils::messageToException($message)
            ];
        }

        if (!empty($updateData)) {
            collect($updateData)->chunk(500)->each(fn($chunk) => FirebaseSenderLog::upsert($chunk->toArray(), ['ulid'], ['message_id', 'sent_at', 'failed_at']));
        }
    }
}
