<?php

namespace MrGarest\FirebaseSender\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MrGarest\FirebaseSender\Target;
use MrGarest\FirebaseSender\TopicCondition;

/**
 * @method static Builder|static messageId(int|string $id)
 * @method static Builder|static serviceAccount(string $serviceAccount)
 * @method static Builder|static deviceToken(string $token)
 * @method static Builder|static topic(TopicCondition|string $topic)
 * @method static Builder|static payload1(string|null $payload)
 * @method static Builder|static payload2(string|null $payload)
 * @method static Builder|static createdBetween(Builder $query, Carbon $start, Carbon $end)
 * @method static Builder|static sentBetween(Builder $query, Carbon $start, Carbon $end)
 * @method static Builder|static scheduledBetween(Builder $query, Carbon $start, Carbon $end)
 * @method static Builder|static failedBetween(Builder $query, Carbon $start, Carbon $end)
 */
class FirebaseSenderLog extends Model
{
    protected $fillable = [
        'message_id',
        'service_account',
        'target',
        'to',
        'payload_1',
        'payload_2',
        'sent_at',
        'failed_at',
        'scheduled_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeMessageId(Builder $query, int|string $id): Builder
    {
        return $query->where('message_id', $id);
    }

    public function scopeServiceAccount(Builder $query, string $serviceAccount): Builder
    {
        return $query->where('service_account', $serviceAccount);
    }

    public function scopeDeviceToken(Builder $query, string $token): Builder
    {
        return $query->where('target', Target::TOKEN)->where('to', $token);
    }

    public function scopeTopic(Builder $query, TopicCondition|string $topic): Builder
    {
        $target = $to = null;

        if (is_string($topic)) {
            $target = Target::TOPIC;
            $to = $topic;
        }

        if ($topic instanceof TopicCondition) {
            $target = Target::CONDITION;
            $to = $topic->toCondition();
        }

        if ($target === null || $to === null) {
            throw new \InvalidArgumentException('Invalid topic condition provided.');
        }

        return $query->where('target', $target)->where('to', $to);
    }

    public function scopePayload1(Builder $query, ?string $payload): Builder
    {
        return $query->where('payload_1', $payload);
    }

    public function scopePayload2(Builder $query, ?string $payload): Builder
    {
        return $query->where('payload_2', $payload);
    }

    public function scopeCreatedBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeSentBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('sent_at', [$start, $end]);
    }

    public function scopeScheduledBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('scheduled_at', [$start, $end]);
    }

    public function scopeFailedBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('failed_at', [$start, $end]);
    }
}
