<?php

namespace Garest\FirebaseSender\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Garest\FirebaseSender\FirebaseSender;
use Garest\FirebaseSender\Models\FirebaseSenderLog;
use Garest\FirebaseSender\Utils;

class FirebaseSenderJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds during which a task can run before timing out.
     *
     * @var int
     */
    public $timeout;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public bool $logsEnabled,
        public string $serviceAccount,
        public array $messages,
        public array|null $ulids,
    ) {
        $this->timeout = config('firebase-sender.job.send_timeout', 600);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $firebaseSender = new FirebaseSender($this->serviceAccount);
        $firebaseSender->logEnabled(false);
        $firebaseSender->setMessages($this->messages);
        $results = $firebaseSender->send();

        if (!$this->logsEnabled || empty($this->ulids)) {
            return;
        }

        $updateData = [];
        foreach ($results->messages as $index => $message) {
            $ulid = $this->ulids[$index] ?? null;
            if (!$ulid) continue;

            $updateData[] = [
                'ulid' => $ulid,
                'service_account' => $this->serviceAccount,
                'message_id' => $message->success ? $message->messageId : null,
                'target' => $message->target,
                'to' => $message->address,
                'sent_at' => $message->success ? $message->datetime : null,
                'failed_at' => !$message->success ? $message->datetime : null,
                'exception' => Utils::messageToException($message)
            ];
        }

        if (!empty($updateData)) {
            collect($updateData)->chunk(500)->each(fn($chunk) => FirebaseSenderLog::upsert($chunk->toArray(), ['ulid'], ['message_id', 'sent_at', 'failed_at', 'exception']));
        }
    }
}
