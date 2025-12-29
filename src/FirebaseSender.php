<?php

namespace MrGarest\FirebaseSender;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Response;
use MrGarest\FirebaseSender\DTO\GoogleAccessToken;
use MrGarest\FirebaseSender\GoogleService;
use MrGarest\FirebaseSender\DTO\MessageError;
use MrGarest\FirebaseSender\DTO\MessageResult;
use MrGarest\FirebaseSender\DTO\SendReport;
use MrGarest\FirebaseSender\Exceptions\AccessTokenMissingException;
use MrGarest\FirebaseSender\Exceptions\MessageEmptyException;
use MrGarest\FirebaseSender\Exceptions\MissingMessageContentException;
use MrGarest\FirebaseSender\Target;
use MrGarest\FirebaseSender\Exceptions\MissingMessageRecipientException;
use MrGarest\FirebaseSender\Exceptions\ServiceAccountException;
use MrGarest\FirebaseSender\Utils;
use MrGarest\FirebaseSender\TopicCondition;
use MrGarest\FirebaseSender\Jobs\FirebaseSenderJob;
use MrGarest\FirebaseSender\Models\FirebaseSenderLog;
use MrGarest\FirebaseSender\Push\AndroidPush;
use MrGarest\FirebaseSender\Push\ApnsPush;
use MrGarest\FirebaseSender\Push\NotificationPush;
use MrGarest\FirebaseSender\Push\WebPush;

class FirebaseSender
{
    private $serviceAccountName = null;
    private $serviceAccount = null;
    private array $to = [['target' => null, 'address' => null]];
    private bool $logsEnabled = false;
    private ?array $payloads = null;
    private ?GoogleAccessToken $authToken = null;
    private int $groupIndex = 0;

    /** @var AndroidPush[] */
    private array $android = [];

    /** @var ApnsPush[] */
    private array $apns = [];

    /** @var WebPush[] */
    private array $webpush = [];

    /** @var NotificationPush[] */
    private array $notification = [];

    private ?array $messageData = [];

    private ?array $messages = null;

    /**
     * Constructor of the class that initializes an object for interacting with Firebase Sender.
     *
     * @param string $serviceAccountName Name from the `service_accounts` array located in the `config/firebase-sender.php` file.
     * 
     * @throws Ex\ServiceAccountException
     */
    public function __construct(string $serviceAccountName)
    {
        $this->serviceAccountName = $serviceAccountName;
        $this->serviceAccount = config('firebase-sender.service_accounts.' . $serviceAccountName);
        if ($this->serviceAccount === null) throw new ServiceAccountException();
    }

    /**
     * Clear all set notification data.
     */
    public function clear(): void
    {
        $this->to = [['target' => null, 'address' => null]];
        $this->groupIndex = 0;
        $this->android = [];
        $this->apns = [];
        $this->webpush = [];
        $this->notification = [];
        $this->messageData = [];
        $this->messages = [];
        $this->payloads = null;
    }

    /**
     * Sets the index for a group of messages.
     *
     * @param string $index
     */
    public function setGroup(int $index): void
    {
        $this->groupIndex = $index;
    }

    /**
     * Returns the count of groups.
     * 
     * @return int
     */
    public function getGroupCount(): int
    {
        return max(
            count($this->notification ?? []),
            count($this->android ?? []),
            count($this->apns ?? []),
            count($this->webpush ?? []),
            count($this->messageData ?? []),
            0
        );
    }

    /**
     * Sets the device token for the recipient.
     *
     * @param string $token The device token string.
     */
    public function setDeviceToken(string $token): void
    {
        $this->to[$this->groupIndex] = [
            'target' => Target::TOKEN,
            'address' => $token,
        ];
    }

    /**
     * Sets the topic or complex topic condition for the recipient.
     *
     * @param TopicCondition|string $topic Either a simple topic string or a complex TopicCondition object.
     * 
     * @throws \InvalidArgumentException if less than two topic conditions are defined when using TopicCondition
     * @throws Ex\MissingTopicConditionOperatorException if the condition operator is missing when using TopicCondition
     */
    public function setTopic(TopicCondition|string $topic): void
    {
        if (is_string($topic)) {
            $this->to[$this->groupIndex] = [
                'target' => Target::TOPIC,
                'address' => $topic,
            ];
            return;
        }

        if ($topic instanceof TopicCondition) {
            $this->to[$this->groupIndex] = [
                'target' => Target::CONDITION,
                'address' => $topic->toCondition(),
            ];
            return;
        }
    }

    /**
     * Set the notification payload data.
     *
     * @param NotificationPush|null $notification
     */
    public function setNotification(?NotificationPush $notification): void
    {
        $this->notification[$this->groupIndex] = $notification;
    }

    /**
     * Set the notification payload for APNs (Apple Push Notification Service).
     *
     * @param ApnsPush|null $apns
     */
    public function setApns(?ApnsPush $apns): void
    {
        $this->apns[$this->groupIndex] = $apns;
    }

    /**
     * Set the notification payload for Android.
     *
     * @param AndroidPush|null $android
     */
    public function setAndroid(?AndroidPush $android): void
    {
        $this->android[$this->groupIndex] = $android;
    }

    /**
     * Set the payload for the web push notification.
     *
     * @param WebPush|null $web
     */
    public function setWeb(?WebPush $web): void
    {
        $this->webpush[$this->groupIndex] = $web;
    }

    /**
     * Set the custom data payload.
     *
     * @param array|null $data
     */
    public function setData(?array $data): void
    {
        $this->messageData[$this->groupIndex] = $data;
    }

    /**
     * Set the payload of the custom message
     * This allows you to specify a custom array of data that will be sent as the message body in the FCM request.
     *
     * @param array|null $message 
     * 
     * @deprecated Guide to transitioning to V3: https://github.com/mrgarest/laravel-firebase-sender/blob/master/migrating-to-v3.md
     */
    public function setMessage(?array $message): void
    {
        $this->messages = [$message];
    }

    /**
     * Set the payload of the custom messages
     * This allows you to specify a custom array of data that will be sent as the message body in the FCM request.
     *
     * @param array|null $messages 
     */
    public function setMessages(?array $messages): void
    {
        $this->messages = $messages;
    }

    /**
     * Enables or disables logging of the notification sending event to the database.
     *
     * @param bool $enabled Whether to enable logging (default: true).
     * @param string|array|null $payload1 Additional payload data to store with the log (optional).
     * @param string|array|null $payload2 Additional payload data to store with the log (optional).
     * 
     * @deprecated Guide to transitioning to V3: https://github.com/mrgarest/laravel-firebase-sender/blob/master/migrating-to-v3.md
     */
    public function setLog(bool $enabled = true, ?string $payload1 = null, ?string $payload2 = null): void
    {
        $this->logEnabled($enabled);
        $this->payloads[0]['p1'] = $payload1;
        $this->payloads[0]['p2'] = $payload2;
    }

    /**
     * Enables log recording.
     *
     * @param bool $enabled
     */
    public function logEnabled(bool $enabled = true): void
    {
        $this->logsEnabled = $enabled;
    }

    /**
     * Adds payload to the log
     *
     * @param string|null $payload
     */
    public function setPayload1(?string $payload = null): void
    {
        $this->payloads[$this->groupIndex]['p1'] = $payload;
    }

    /**
     * Adds payload to the log
     *
     * @param string|null $payload
     */
    public function setPayload2(?string $payload = null): void
    {
        $this->payloads[$this->groupIndex]['p2'] = $payload;
    }

    /**
     * Sends notifications.
     *
     * @return SendReport
     * 
     * @throws AccessTokenMissingException Occurs if the authorization token could not be obtained from Google.
     * @throws MessageEmptyException Occurs when the message is empty.
     * @throws MissingMessageRecipientException Occurs if the group does not have a message recipient.
     * @throws MissingMessageContentException Occurs when a message contains only the recipient without any content.
     */
    public function send(): SendReport
    {
        if ($this->authToken === null || $this->authToken->isExpiringSoon()) {
            $authToken = GoogleService::getAccessToken($this->serviceAccount);

            if ($authToken === null) throw new AccessTokenMissingException();

            $this->authToken = $authToken;
        }

        $messages = $this->makeMessages();
        $responses = GoogleService::poolMessage(
            $this->serviceAccount['project_id'],
            $this->authToken->accessToken,
            $messages
        );

        $timezone = config('app.timezone', 'UTC');
        $now = Carbon::now();

        $insertLog = [];
        /** @var MessageResult[] $messagesResult */
        $messagesResult = [];
        foreach ($messages as $index => $message) {
            $response = $responses["msg_{$index}"] ?? null;
            $hasResponse = $response instanceof Response;

            $isSuccess = $hasResponse && $response->successful();
            $data = $hasResponse ? $response->json() : [];

            $serverDate = $hasResponse ? $response->header('Date') : null;
            $datetime = $serverDate ? Carbon::parse($serverDate)->setTimezone($timezone) : $now;

            $recipient = Utils::getRecipient($message);

            $messageResult = new MessageResult(
                success: $isSuccess,
                messageId: $isSuccess && isset($data['name']) ? basename($data['name']) : null,
                target: $recipient['target'],
                address: $recipient['address'],
                datetime: $datetime,
                error: !$isSuccess ? new MessageError(
                    code: $data['error']['code'] ?? null,
                    status: $data['error']['status'] ?? null,
                    message: $data['error']['message'] ?? null,
                ) : null
            );

            $messagesResult[] = $messageResult;

            // Data for insertion into the log/
            if (!$this->logsEnabled) continue;
            $insertLog[] = [
                'ulid' => (string) Str::ulid(),
                'service_account' => $this->serviceAccountName,
                'message_id' => $messageResult->messageId,
                'target' => $messageResult->target,
                'to' => $messageResult->address,
                'payload_1' => $this->payloads[$index]['p1'] ?? null,
                'payload_2' => $this->payloads[$index]['p2'] ?? null,
                'exception' => Utils::messageToException($messageResult),
                'sent_at' => $isSuccess ? $datetime : null,
                'failed_at' => !$isSuccess ? $datetime : null,
                'scheduled_at' => null,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        if ($this->logsEnabled && !empty($insertLog)) {
            collect($insertLog)->chunk(500)->each(fn($chunk) => FirebaseSenderLog::insert($chunk->toArray()));
        }

        return new SendReport(
            totalCount: count($messagesResult),
            successCount: collect($messagesResult)->where('success', true)->count(),
            failureCount: collect($messagesResult)->where('success', false)->count(),
            messages: $messagesResult
        );
    }

    /**
     * Schedules a notification to be sent at a specific time using a queued job.
     *
     * @param Carbon $scheduledAt Date when notification should be sent.
     * @param int $chunkLength Allows you to split messages into chunks.
     * @param int $maxRand Adds a random number of seconds to the chunk dispatch schedule.
     * 
     * @throws MessageEmptyException Occurs when the message is empty.
     * @throws MissingMessageRecipientException Occurs if the group does not have a message recipient.
     * @throws MissingMessageContentException Occurs when a message contains only the recipient without any content.
     */
    public function sendJob(Carbon $scheduledAt, int $chunkLength = 10, int $maxRand = 0): void
    {
        $messages = $this->makeMessages();

        $insertLog = [];

        // Breaking down an array into parts and processing them
        foreach (array_chunk($messages, $chunkLength, true) as $chunk) {
            $ulids = [];

            if ($this->logsEnabled) {
                $now = Carbon::now();
                foreach ($messages as $index => $message) {
                    $ulid = (string) Str::ulid();
                    $ulids[] = $ulid;
                    $recipient = Utils::getRecipient($message);
                    $insertLog[] = [
                        'ulid' => $ulid,
                        'service_account' => $this->serviceAccountName,
                        'message_id' => null,
                        'target' => $recipient['target'],
                        'to' => $recipient['address'],
                        'payload_1' => $this->payloads[$index]['p1'] ?? null,
                        'payload_2' => $this->payloads[$index]['p2'] ?? null,
                        'sent_at' => null,
                        'failed_at' => null,
                        'scheduled_at' => $scheduledAt,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
            }

            FirebaseSenderJob::dispatch(
                $this->logsEnabled,
                $this->serviceAccountName,
                $chunk,
                $ulids,
            )->delay($maxRand === 0 ? $scheduledAt : $scheduledAt->copy()->addSecond(mt_rand(0, $maxRand)));
        }

        if (!empty($insertLog)) {
            collect($insertLog)->chunk(500)->each(fn($chunk) => FirebaseSenderLog::insert($chunk->toArray()));
        }
    }

    /**
     * Creating a messages body for a notification.
     *
     * @return array|null
     * 
     * @throws MessageEmptyException Occurs when the message is empty.
     * @throws MissingMessageRecipientException Occurs if the group does not have a message recipient.
     * @throws MissingMessageContentException Occurs when a message contains only the recipient without any content.
     */
    protected function makeMessages(): array
    {
        if (!empty($this->messages)) {
            return $this->messages;
        }
        $groupCount = $this->getGroupCount();
        if ($groupCount === 0) {
            throw new MessageEmptyException();
        }

        $messages = [];
        for ($i = 0; $i < $groupCount; $i++) {

            $target = $this->to[$i]['target'] ?? null;
            $address = $this->to[$i]['address'] ?? null;

            if ($target == null || $address == null) {
                throw new MissingMessageRecipientException($i);
            }

            $message = [
                $target => $address,
                'notification' => ($this->notification[$i] ?? null)?->make(),
                'android' => ($this->android[$i] ?? null)?->make(),
                'apns' => ($this->apns[$i] ?? null)?->make(),
                'webpush' => ($this->webpush[$i] ?? null)?->make(),
                'data' => $this->messageData[$i] ?? null,
            ];

            $filtered = Utils::nullFilter($message);

            // Checks whether there is anything else in the message besides the recipient
            if ($filtered && count($filtered) <= 1) {
                throw new MissingMessageContentException($i);
            }

            if ($filtered) {
                $messages[] = $filtered;
            }
        }

        if (empty($messages)) {
            throw new MessageEmptyException();
        }

        return $messages;
    }
}
