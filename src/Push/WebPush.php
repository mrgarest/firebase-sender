<?php

namespace MrGarest\FirebaseSender\Push;

use MrGarest\FirebaseSender\Utils;

/**
 * Represents data structure for push notifications.
 *
 * @property string|null $title Set the title of the notification.
 * @property string|null $body Set the body of the notification.
 * @property string|null $link Set the link that will open when the user clicks on the notification. HTTPS is required.
 * @property string|null $image Set a link to the image
 * @property int|null $ttl Sets the time to live (TTL) for the notification.
 * @property bool $priorityHigh Set the priority of the notification.
 */
class WebPush
{
    public ?string $title;
    public ?string $body;
    public ?string $link;
    public ?string $image;
    public bool $priorityHigh;
    public ?int $ttl;

    public function __construct(
        ?string $title = null,
        ?string $body = null,
        ?string $link = null,
        ?string $image = null,
        ?int $ttl = null,
        bool $priorityHigh = false,
    ) {
        $this->title = $title;
        $this->body = $body;
        $this->link = $link;
        $this->image = $image;
        $this->ttl = $ttl;
        $this->priorityHigh = $priorityHigh;
    }

    /**
     * Set the title of the notification.
     *
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * Set the body of the notification.
     *
     * @param string|null $body
     */
    public function setBody(?string $body): void
    {
        $this->body = $body;
    }
    /**
     * Set the link that will open when the user clicks on the notification. HTTPS is required.
     *
     * @param string $link
     */
    public function setLink(?string $link): void
    {
        $this->link = $link;
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
     * Set a link to the image
     *
     * @param string $url
     */
    public function setImage(?string $url): void
    {
        $this->image = $url;
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
     * Build the notification data as an array, skipping null values.
     *
     * @return array|null The filtered array of notification data.
     */
    public function make(): array|null
    {
        return Utils::nullFilter([
            'headers' => Utils::nullFilter([
                'Urgency' => $this->priorityHigh ? 'high' : null,
                'TTL' => $this->ttl,
            ]),
            'notification' => Utils::nullFilter([
                'title' => $this->title,
                'body' => $this->body,
                'image' => $this->image
            ]),
            'fcm_options' => Utils::nullFilter([
                'link' => $this->link
            ]),
        ]);
    }
}
