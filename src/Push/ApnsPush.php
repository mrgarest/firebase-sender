<?php

namespace Garest\FirebaseSender\Push;

use Garest\FirebaseSender\Utils;

/**
 * Represents data structure for apns push notifications.
 *
 * @property string|null $title Set the notification title.
 * @property string|null $titleLocKey Set the localization key for the title.
 * @property array|null $titleLocArgs Set the localization arguments for the title.
 * @property string|null $body Set the notification body text.
 * @property string|null $bodyLocKey Set the localization key for the body.
 * @property array|null $bodyLocArgs Set the localization arguments for the body.
 * @property string|null $category Set the notification category
 * @property int|null $priority Set the priority of the notification (e.g., 10 = high, 5 = normal). 
 * @property int|null $badge Set the number that will be displayed in the badge on your app icon. Enter 0 to remove the 
 * @property string|null $sound Set the name of the sound file.
 * @property string|null $image Set a link to the image
 */
class ApnsPush extends Push
{
    public ?string $category;
    public ?int $priority;
    public ?int $badge;
    public ?string $sound;

    public function __construct(
        ?string $title = null,
        ?string $titleLocKey = null,
        ?array $titleLocArgs = null,
        ?string $body = null,
        ?string $bodyLocKey = null,
        ?array $bodyLocArgs = null,
        ?string $category = null,
        ?int $priority = null,
        ?int $badge = null,
        ?string $sound = null,
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
        $this->category = $category;
        $this->priority = $priority;
        $this->badge = $badge;
        $this->sound = $sound;
    }

    /**
     * Set the priority of the notification.
     *
     * Apple Push Notification Service (APNs) supports different priority levels:
     * - 10 (high priority): The notification is sent immediately.
     * - 5 (low priority): The notification is sent at a time that conserves power on the device.
     *
     * @param int|null $priority
     */
    public function setPriority(?int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * Set the number that will be displayed in the badge on your app icon. Enter 0 to remove the current badge, if any.
     *
     * @param int|null $badge
     */
    public function setBadge(?int $badge): void
    {
        $this->badge = $badge;
    }

    /**
     * Set the name of the sound file in the main package of your app or in the Library/Sounds folder of your app's container directory. 
     * Specify the string “default” to play the system sound. Use this key for normal notifications.
     *
     * @param int|null $sound
     */
    public function setSound(?string $sound): void
    {
        $this->sound = $sound;
    }

    /**
     * Set the notification category.
     *
     * @param int|null $category
     */
    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    /**
     * Build the notification data as an array, skipping null values.
     *
     * @return array|null The filtered array of notification data.
     */
    public function make(): array|null
    {
        $aps = Utils::nullFilter([
            'alert' => Utils::nullFilter([
                'title' => $this->title,
                'body' => $this->body,
                'title-loc-key' => $this->titleLocKey,
                'title-loc-args' => $this->titleLocArgs,
                'loc-key' => $this->bodyLocKey,
                'loc-args' => $this->bodyLocArgs,
            ]),
            'badge' => $this->badge,
            'sound' => $this->sound,
            'mutable-content' => $this->image !== null ? 1 : null,
        ]);

        return Utils::nullFilter([
            'headers' => Utils::nullFilter([
                'apns-priority' => $this->priority ? (string) $this->priority : null,
            ]),
            'payload' => $aps ? [
                'aps' => $aps,
            ] : null,
            'fcm_options' => Utils::nullFilter([
                'image' => $this->image,
            ])
        ]);
    }
}
