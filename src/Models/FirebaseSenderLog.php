<?php

namespace Garest\FirebaseSender\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Garest\FirebaseSender\Target;
use Garest\FirebaseSender\TopicCondition;
use Illuminate\Support\Str;

/**
 * @method static Builder|static ulid(int|string $id) Filter by ulid.
 * @method static Builder|static messageId(int|string $id) Filter by message ID.
 * @method static Builder|static serviceAccount(string $serviceAccount) Filter by service account name.
 * @method static Builder|static deviceToken(string $token) Filter by device token target.
 * @method static Builder|static topic(TopicCondition|string $topic) Filter by exact topic or topic condition.
 * @method static Builder|static matchTopic(string $topic, bool $onlyCondition = false) Filter by exact or partial topic match, including within topic conditions.
 * @method static Builder|static payload1(string|null $payload) Filter by first payload value.
 * @method static Builder|static payload2(string|null $payload) Filter by second payload value.
 * @method static Builder|static createdBetween(Carbon $start, Carbon $end) Filter records created between two dates.
 * @method static Builder|static sentBetween(Carbon $start, Carbon $end) Filter records sent between two dates.
 * @method static Builder|static scheduledBetween(Carbon $start, Carbon $end) Filter records scheduled between two dates.
 * @method static Builder|static failedBetween(Carbon $start, Carbon $end) Filter records failed between two dates.
 * @method static Builder<static>|FirebaseSenderLog newModelQuery()
 * @method static Builder<static>|FirebaseSenderLog newQuery()
 * @method static Builder<static>|FirebaseSenderLog query()
 * @mixin \Eloquent
 */
class FirebaseSenderLog extends Model
{
    use Prunable;

    protected $fillable = [
        'ulid',
        'service_account',
        'message_id',
        'target',
        'to',
        'payload_1',
        'payload_2',
        'exception',
        'sent_at',
        'failed_at',
        'scheduled_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'ulid' => 'string',
        'service_account' => 'string',
        'message_id' => 'string',
        'target' => 'string',
        'to' => 'string',
        'payload_1' => 'string',
        'payload_2' => 'string',
        'exception' => 'string',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }

    /**
     * Define the pruning logic for old log entries.
     */
    public function prunable(): Builder
    {
        $hours = config('firebase-sender.log.prune_after');

        // If prune_after is not set, return an empty query to prevent pruning any records.
        if (is_null($hours)) {
            return static::whereRaw('1 = 0');
        }

        return static::where('created_at', '<=', Carbon::now()->subHours($hours));
    }

    /**
     * Filter by ulid.
     *
     * @param Builder $query
     * @param string $ulid
     * @return Builder
     */
    public function scopeUlid(Builder $query, string $ulid): Builder
    {
        return $query->where('ulid', $ulid);
    }

    /**
     * Filter by message ID.
     *
     * @param Builder $query
     * @param int|string $id
     * @return Builder
     */
    public function scopeMessageId(Builder $query, int|string $id): Builder
    {
        return $query->where('message_id', $id);
    }

    /**
     * Filter by service account name.
     *
     * @param Builder $query
     * @param string $serviceAccount
     * @return Builder
     */
    public function scopeServiceAccount(Builder $query, string $serviceAccount): Builder
    {
        return $query->where('service_account', $serviceAccount);
    }

    /**
     * Filter by device token target.
     *
     * @param Builder $query
     * @param string $token
     * @return Builder
     */
    public function scopeDeviceToken(Builder $query, string $token): Builder
    {
        return $query->where('target', Target::TOKEN)->where('to', $token);
    }

    /**
     * Filter by exact topic or topic condition.
     *
     * Finds records where the topic or condition exactly matches the given value.
     *
     * @param Builder $query
     * @param TopicCondition|string $topic
     * @return Builder
     *
     * @throws \InvalidArgumentException
     */
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

    /**
     * Filter by exact or partial topic match, including within topic conditions.
     *
     * Finds records where the topic exactly matches or is included within topic conditions.
     *
     * @param Builder $query
     * @param string $topic
     * @param bool $onlyCondition  Whether to filter only condition targets.
     * @return Builder
     */
    public function scopeMatchTopic(Builder $query, string $topic, bool $onlyCondition = false): Builder
    {
        if ($onlyCondition) {
            return $query->where('target', Target::CONDITION)
                ->where('to', 'like', "%'{$topic}' in topics%");
        }

        return $query->where(function ($q) use ($topic) {
            $q->where('target', Target::TOPIC)
                ->where('to', $topic)
                ->orWhere(function ($q) use ($topic) {
                    $q->where('target', Target::CONDITION)
                        ->where('to', 'like', "%'{$topic}' in topics%");
                });
        });
    }

    /**
     * Filter by first payload value.
     *
     * @param Builder $query
     * @param string|null $payload
     * @return Builder
     */
    public function scopePayload1(Builder $query, ?string $payload): Builder
    {
        return $query->where('payload_1', $payload);
    }

    /**
     * Filter by second payload value.
     *
     * @param Builder $query
     * @param string|null $payload
     * @return Builder
     */
    public function scopePayload2(Builder $query, ?string $payload): Builder
    {
        return $query->where('payload_2', $payload);
    }

    /**
     * Filter records created between two dates.
     *
     * @param Builder $query
     * @param Carbon $start
     * @param Carbon $end
     * @return Builder
     */
    public function scopeCreatedBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Filter records sent between two dates.
     *
     * @param Builder $query
     * @param Carbon $start
     * @param Carbon $end
     * @return Builder
     */
    public function scopeSentBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('sent_at', [$start, $end]);
    }

    /**
     * Filter records scheduled between two dates.
     *
     * @param Builder $query
     * @param Carbon $start
     * @param Carbon $end
     * @return Builder
     */
    public function scopeScheduledBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('scheduled_at', [$start, $end]);
    }

    /**
     * Filter records failed between two dates.
     *
     * @param Builder $query
     * @param Carbon $start
     * @param Carbon $end
     * @return Builder
     */
    public function scopeFailedBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('failed_at', [$start, $end]);
    }
}
