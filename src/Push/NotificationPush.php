<?php

namespace Garest\FirebaseSender\Push;

use Garest\FirebaseSender\Utils;

/**
 * Represents data structure for push notifications.
 *
 * @property string|null $title Set the title of the notification.
 * @property string|null $body Set the body of the notification.
 */
class NotificationPush
{
    public ?string $title;
    public ?string $body;

    public function __construct(
        ?string $title = null,
        ?string $body = null,
    ) {
        $this->title = $title;
        $this->body = $body;
    }

    /**
     * Set the title of the notification.
     *
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Set the body of the notification.
     *
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * Build the notification data as an array, skipping null values.
     *
     * @return array|null The filtered array of notification data.
     */
    public function make(): array|null
    {
        return Utils::nullFilter([
            'title' => $this->title,
            'body' => $this->body,
        ]);
    }
}
