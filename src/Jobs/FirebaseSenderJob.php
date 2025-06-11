<?php

namespace MrGarest\FirebaseSender\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use MrGarest\FirebaseSender\FirebaseSender;
use MrGarest\FirebaseSender\Models\FirebaseSenderLog;

class FirebaseSenderJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int|null $id,
        public string $serviceAccount,
        public array $message,
    ) {
        
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $firebaseSender = new FirebaseSender($this->serviceAccount);
        $firebaseSender->setLog(false);
        $firebaseSender->setMessage($this->message);
        $isSend = $firebaseSender->send();

        $now = Carbon::now();
        optional(FirebaseSenderLog::find($this->id))->update([
            'message_id' => $firebaseSender->getMessageIdFromResponse(),
            'sent_at' => $isSend ? $now : null,
            'failed_at' => $isSend ? null : $now
        ]);
    }

    /**
     * Failed job.
     */
    public function failed(\Exception $exception)
    {
        optional(FirebaseSenderLog::find($this->id))->update([
            'failed_at' => Carbon::now()
        ]);
    }
}
