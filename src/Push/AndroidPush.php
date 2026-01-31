<?php

namespace Garest\FirebaseSender\Push;

use Garest\FirebaseSender\Utils;

/**
 * Represents data structure for Android push notifications.
 *
 * @property string|null $title Set the notification title.
 * @property string|null $titleLocKey Set the localization key for the title.
 * @property array|null $titleLocArgs Set the localization arguments for the title.
 * @property string|null $body Set the notification body text.
 * @property string|null $bodyLocKey Set the localization key for the body.
 * @property array|null $bodyLocArgs Set the localization arguments for the body.
 * @property string|null $channelId Set the notification channel ID.
 * @property bool $priorityHigh Set the priority of the notification.
 * @property int|null $ttl Sets the time to live (TTL) for the notification.
 * @property string|null $image Set a link to the image
 */
class AndroidPush extends Push
{
    public ?string $channelId;
    public bool $priorityHigh;
    public ?int $ttl;

    public function __construct(
        ?string $title = null,
        ?string $titleLocKey = null,
        ?array $titleLocArgs = null,
        ?string $body = null,
        ?string $bodyLocKey = null,
        ?array $bodyLocArgs = null,
        ?string $channelId = null,
        bool $priorityHigh = false,
        ?int $ttl = null,
        ?string $image = null,
    ) {
        parent::__construct(
            title: $title,
            titleLocKey: $titleLocKey,
            titleLocArgs: $titleLocArgs,
            body: $body,
            bodyLocKey: $bodyLocKey,
            bodyLocArgs: $bodyLocArgs,
            image: $image,
        );
        $this->channelId = $channelId;
        $this->priorityHigh = $priorityHigh;
        $this->ttl = $ttl;
    }

    /**
     * Sets the time to live (TTL) for the notification.
     * 
     * @param int|null $seconds Time duration, in seconds.
     */
    public function setTimeToLive(?int $seconds): void
    {
        $this->ttl = $seconds;
    }

    /**
     * Set the priority of the notification.
     * 
     * @param bool $priorityHigh
     */
    public function setPriorityHigh(bool $priorityHigh): void
    {
        $this->priorityHigh = $priorityHigh;
    }

    /**
     * Set the notification channel ID (new in Android O).
     * 
     * @param string|null $channelId
     */
    public function setChannelId(?string $channelId): void
    {
        $this->channelId = $channelId;
    }

    /**
     * Build the notification data as an array, skipping null values.
     *
     * @return array|null The filtered array of notification data.
     */
    public function make(): array|null
    {
        return Utils::nullFilter([
            'notification' => Utils::nullFilter([
                'title' => $this->title,
                'title_loc_key' => $this->titleLocKey,
                'title_loc_args' => $this->titleLocArgs,
                'body' => $this->body,
                'body_loc_key' => $this->bodyLocKey,
                'body_loc_args' => $this->bodyLocArgs,
                'channel_id' => $this->channelId,
                'image' => $this->image,
            ]),
            'priority' => $this->priorityHigh ? 'high' : null,
            'ttl' => $this->ttl ? $this->ttl . 's' : null
        ]);
    }
}
