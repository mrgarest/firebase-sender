<?php

namespace MrGarest\FirebaseSender;

use Google\Auth\CredentialsLoader;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use MrGarest\FirebaseSender\Target;
use MrGarest\FirebaseSender\Exceptions as Ex;
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
    private array $to = ['target' => null, 'address' => null];
    private array $dbLog = ['enabled' => false, 'payloads' => [null, null]];
    private $authToken = null;
    private $sendResponse = null;
    private ?AndroidPush $android = null;
    private ?ApnsPush $apns = null;
    private ?WebPush $webpush = null;
    private ?NotificationPush $notification = null;
    private ?array $messageData = null;
    private ?array $message = null;

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
        if ($this->serviceAccount === null) throw new Ex\ServiceAccountException();
    }

    /**
     * Sets a high priority for the notification.
     * @deprecated Migrating to v2: https://github.com/mrgarest/laravel-firebase-sender/blob/master/migrating-to-v2.md
     */
    public function setHighPriority()
    {
        ($this->android ??= new AndroidPush())->setPriorityHigh(true);
        ($this->apns ??= new ApnsPush())->setPriority(10);
    }

    /**
     * Sets the time to live (TTL) for the notification.
     * 
     * @param int $seconds Time duration, in seconds.
     * @deprecated Migrating to v2: https://github.com/mrgarest/laravel-firebase-sender/blob/master/migrating-to-v2.md
     */
    public function setTimeToLive(int $seconds)
    {
        ($this->android ??= new AndroidPush())->setTimeToLive($seconds);
    }

    /**
     * Sets the token of a specific device to which the notification will be sent.
     *
     * @param string $token
     *
     * @deprecated Use the new setDeviceToken
     */
    public function setTokenDevices(string $token)
    {
        $this->setDeviceToken($token);
    }

    /**
     * Sets the device token for the recipient.
     *
     * @param string $token The device token string.
     */
    public function setDeviceToken(string $token)
    {
        $this->to = [
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
    public function setTopic(TopicCondition|string $topic)
    {
        if (is_string($topic)) {
            $this->to = [
                'target' => Target::TOPIC,
                'address' => $topic,
            ];
            return;
        }

        if ($topic instanceof TopicCondition) {
            $this->to = [
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
        $this->notification = $notification;
    }

    /**
     * Set the notification payload for APNs (Apple Push Notification Service).
     *
     * @param ApnsPush|null $apns
     */
    public function setApns(?ApnsPush $apns): void
    {
        $this->apns = $apns;
    }

    /**
     * Set the notification payload for Android.
     *
     * @param AndroidPush|null $android
     */
    public function setAndroid(?AndroidPush $android): void
    {
        $this->android = $android;
    }

    /**
     * Set the payload for the web push notification.
     *
     * @param WebPush|null $web
     */
    public function setWeb(?WebPush $web): void
    {
        $this->webpush = $web;
    }

    /**
     * Set the custom data payload.
     *
     * @param array|null $data
     */
    public function setData(?array $data): void
    {
        $this->messageData = $data;
    }

    /**
     * Set the payload of the custom message
     * This allows you to specify a custom array of data that will be sent as the message body in the FCM request.
     *
     * @param array|null $message 
     */
    public function setMessage(?array $message)
    {
        $this->message = $message;
    }

    /**
     * Sets the title of the notification.
     *
     * @param string $str
     * @deprecated Migrating to v2: https://github.com/mrgarest/laravel-firebase-sender/blob/master/migrating-to-v2.md
     */
    public function setTitle(string $str)
    {
        ($this->notification ??= new NotificationPush())->setTitle($str);
    }

    /**
     * Sets the localization key for the notification title for all platforms.
     *
     * @param string $key Localization key.
     * @param array|null $args Array of arguments (optional).
     * @deprecated Migrating to v2: https://github.com/mrgarest/laravel-firebase-sender/blob/master/migrating-to-v2.md
     */
    public function setTitleLocKey(string $key, array|null $args = null)
    {
        $this->setAndroidTitleLocKey($key, $args);
        $this->setApnsTitleLocKey($key, $args);
    }

    /**
     * Sets the localization key for the Android notification title.
     *
     * @param string $key Localization key.
     * @param array|null $args Array of arguments (optional).
     * 
     * @deprecated Migrating to v2: https://github.com/mrgarest/laravel-firebase-sender/blob/master/migrating-to-v2.md
     */
    public function setAndroidTitleLocKey(string $key, array|null $args = null)
    {
        ($this->android ??= new AndroidPush())->setTitleLocKey($key);
        if ($args === null) return;
        $this->android->setTitleLocArgs(Utils::toStrArg($args));
    }

    /**
     * Sets the localization key for the APNs notification title.
     *
     * @param string $key Localization key.
     * @param array|null $args Array of arguments (optional).
     * @deprecated Migrating to v2: https://github.com/mrgarest/laravel-firebase-sender/blob/master/migrating-to-v2.md
     */
    public function setApnsTitleLocKey(string $key, array|null $args = null)
    {
        ($this->apns ??= new ApnsPush())->setTitleLocKey($key);
        if ($args === null) return;
        $this->apns->setTitleLocArgs(Utils::toStrArg($args));
    }

    /**
     * Sets the body of the notification.
     *
     * @param string $str
     * @deprecated Migrating to v2: https://github.com/mrgarest/laravel-firebase-sender/blob/master/migrating-to-v2.md
     */
    public function setBody(string $str)
    {
        ($this->notification ??= new NotificationPush())->setBody($str);
    }

    /**
     * Sets the localization key for the notification body for all platforms.
     *
     * @param string $key Localization key.
     * @param array|null $args Array of arguments (optional).
     * @deprecated Migrating to v2: https://github.com/mrgarest/laravel-firebase-sender/blob/master/migrating-to-v2.md
     */
    public function setBodyLocKey(string $key, array|null $args = null)
    {
        $this->setAndroidBodyLocKey($key, $args);
        $this->setApnsBodyLocKey($key, $args);
    }

    /**
     *Sets the localization key for the Android notification body.
     * 
     * @param string $key Localization key.
     * @param array|null $args Array of arguments (optional).
     * @deprecated Migrating to v2: https://github.com/mrgarest/laravel-firebase-sender/blob/master/migrating-to-v2.md
     */
    public function setAndroidBodyLocKey(string $key, array|null $args = null)
    {
        ($this->android ??= new AndroidPush())->setBodyLocKey($key);
        if ($args === null) return;
        $this->android->setBodyLocArgs(Utils::toStrArg($args));
    }

    /**
     *Sets the localization key for the APNs notification body.
     * 
     * @param string $key Localization key.
     * @param array|null $args Array of arguments (optional).
     * @deprecated Migrating to v2: https://github.com/mrgarest/laravel-firebase-sender/blob/master/migrating-to-v2.md
     */
    public function setApnsBodyLocKey(string $key, array|null $args = null)
    {
        ($this->apns ??= new ApnsPush())->setBodyLocKey($key);
        if ($args === null) return;
        $this->apns->setBodyLocArgs(Utils::toStrArg($args));
    }

    /**
     * Sets a link to an image.
     *
     * @param string|null $url
     * @deprecated Migrating to v2: https://github.com/mrgarest/laravel-firebase-sender/blob/master/migrating-to-v2.md
     */
    public function setImage(string|null $url)
    {
        ($this->android ??= new AndroidPush())->setImage($url);
        ($this->apns ??= new ApnsPush())->setImage($url);
    }

    /**
     * Enables or disables logging of the notification sending event to the database.
     *
     * @param bool $enabled Whether to enable logging (default: true).
     * @param string|array|null $payload1 Additional payload data to store with the log (optional).
     * @param string|array|null $payload2 Additional payload data to store with the log (optional).
     */
    public function setLog(bool $enabled = true, ?string $payload1 = null, ?string $payload2 = null): void
    {
        $this->dbLog = ['enabled' => $enabled, 'payloads' => [$payload1, $payload2]];
    }

    /**
     * Returns the json response data from the last send operation.
     *
     * @return mixed The response data received from Firebase after sending a notification.
     */
    public function getResponse(): mixed
    {
        return $this->sendResponse;
    }

    /**
     * Sends notifications.
     *
     * @return bool `true` if the push notification was successfully sent, `false` otherwise.
     * @throws Ex\AccessTokenMissingException
     * @throws Ex\MessageEmptyException
     */
    public function send(): bool
    {
        if ($this->authToken === null) $this->authToken = $this->getAuthToken();
        if ($this->authToken === null) throw new Ex\AccessTokenMissingException();

        $message = $this->message != null ? $this->message : $this->makeMessage();
        if ($message === null || empty($message)) throw new Ex\MessageEmptyException();

        $response = Http::withToken($this->authToken['access_token'])
            ->withHeaders(['Content-Type' => 'application/json; UTF-8',])
            ->post(
                "https://fcm.googleapis.com/v1/projects/{$this->serviceAccount['project_id']}/messages:send",
                ['message' => $message]
            );

        $this->sendResponse = $response->json();

        if (!$response->successful() || isset($this->sendResponse['error']) || !isset($this->sendResponse['name'])) {
            $this->writeLog(true, [
                'failed_at' => Carbon::now(),
            ]);
            return false;
        }

        $this->writeLog(true, [
            'message_id' => $this->getMessageIdFromResponse(),
            'sent_at' => Carbon::now(),
        ]);

        return true;
    }

    /**
     * Extracts and returns the message ID from the Firebase response.
     *
     * @return string|null The message ID if available, or null if not found in the response.
     */
    public function getMessageIdFromResponse(): ?string
    {
        if (!isset($this->sendResponse['name'])) return null;

        // [1] - project id, [2] - message id
        preg_match('/projects\/(.+?)\/messages\/(.*)/', $this->sendResponse['name'], $matches);

        return $matches[2] ?? null;
    }

    /**
     * Schedules a notification to be sent at a specific time using a queued job.
     *
     * @param Carbon|null $scheduledAt The date and time when the notification should be sent. If null, the job will be dispatched immediately.
     * @throws Ex\MessageEmptyException If the message payload is empty or invalid.
     */
    public function sendJob(?Carbon $scheduledAt = null): void
    {
        $message = $this->message != null ? $this->message : $this->makeMessage();
        if ($message === null || empty($message)) throw new Ex\MessageEmptyException();

        $model = $this->writeLog(false, [
            'scheduled_at' => $scheduledAt
        ]);

        FirebaseSenderJob::dispatch(
            $model != null ? $model->id : null,
            $this->serviceAccountName,
            $message,
        )->delay($scheduledAt);
    }

    /**
     * Writes a log entry to the database about the notification sending event.
     *
     * @param bool $onlyInsert If true, only inserts a new record and returns the created model; otherwise, performs a standard insert.
     * @param array|null $data
     * @return mixed|null Returns the created model if $onlyInsert is true, otherwise null.
     */
    protected function writeLog(bool $onlyInsert, ?array $data)
    {
        if ($this->dbLog['enabled'] !== true) return null;

        $now = Carbon::now();

        $query = [
            'message_id' => $data['message_id'] ?? null,
            'service_account' => $this->serviceAccountName,
            'target' => $this->to['target'],
            'to' => $this->to['address'],
            'payload_1' => $this->dbLog['payloads'][0] ?? null,
            'payload_2' => $this->dbLog['payloads'][1] ?? null,
            'sent_at' => $data['sent_at'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'failed_at' => $data['failed_at'] ?? null,
            'created_at' => $now,
            'updated_at' => $now
        ];

        if (!$onlyInsert) {
            return FirebaseSenderLog::selct('id')->create($query);
        }

        FirebaseSenderLog::insert($query);

        return null;
    }

    /**
     * Creating a message body for a notification.
     *
     * @return array|null
     */
    protected function makeMessage(): ?array
    {
        $message = [
            $this->to['target'] => $this->to['address'],
            'notification' => $this->notification && ($data = $this->notification->make()) ? $data : null,
            'android' => $this->android && ($data = $this->android->make()) ? $data : null,
            'apns' => $this->apns && ($data = $this->apns->make()) ? $data : null,
            'webpush' => $this->webpush && ($data = $this->webpush->make()) ? $data : null,
            'data' => $this->messageData,
        ];

        return Utils::nullFilter($message);
    }

    /**
     * Creates an OAuth2 token for authorization.
     *
     * @return array<mixed>|null 
     */
    public function getAuthToken(): ?array
    {
        $credentials = CredentialsLoader::makeCredentials('https://www.googleapis.com/auth/firebase.messaging', $this->serviceAccount);
        $auth = $credentials->fetchAuthToken();
        if (!isset($auth['access_token'])) return null;

        return $auth;
    }
}
