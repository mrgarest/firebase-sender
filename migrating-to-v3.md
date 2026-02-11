# Migrating to v3

If you are upgrading from version v1 to version v3, you may find the [migration guide from version v1 to version v2](https://github.com/mrgarest/firebase-sender/blob/master/migrating-to-v2.md) useful.

## Removed methods

Most of the methods listed above were obsolete in version v2, so they were removed in version v3.

- `setHighPriority`
- `setTimeToLive`
- `setTokenDevices`
- `setTitle`
- `setTitleLocKey`
- `setAndroidTitleLocKey`
- `setApnsTitleLocKey`
- `setBody`
- `setBodyLocKey`
- `setAndroidBodyLocKey`
- `setApnsBodyLocKey`
- `setImage`
- `getMessageIdFromResponse`
- `getResponse`
- `getAuthToken`
- `setMessage`
- `setLog`

## Deprecated methods

The table below lists deprecated methods and their equivalents. These deprecated methods will be removed in future updates, so you should replace them with their equivalents.

| Deprecated     | Equivalents                                |
| -------------- | ------------------------------------------ |
| `setMessage`   | `setMessages`                              |
| `setLog`       | `logEnabled`, `setPayload1`, `setPayload2` |
| `getAuthToken` | `GoogleService::getAccessToken`            |

## New and updated methods

### setGroup

Now you can send bulk messages by dividing them into groups.

```php
$firebaseSender->setGroup(int $index): void
```

Each group must have its own index, recipient, and message body.

```php
$firebaseSender = new FirebaseSender('MY_SERVICE_ACCOUNT_NAME');

$firebaseSender->setGroup(0);
$firebaseSender->setDeviceToken("MY_DEVICE_TOKEN_1");
$firebaseSender->setNotification(new NotificationPush(
    title: "Lorem ipsum",
    body: 'Lorem ipsum dolor sit amet consectetur adipisicing elit.',
));

$firebaseSender->setGroup(1);
$firebaseSender->setDeviceToken("MY_DEVICE_TOKEN_2");
$firebaseSender->setNotification(new NotificationPush(
    title: "Lorem ipsum",
    body: 'Lorem ipsum dolor sit amet consectetur adipisicing elit.',
));

$firebaseSender->send();
```

### getGroupCount

This method allows you to find out the total count of groups.

```php
$firebaseSender->getGroupCount(): int
```

### Log

Data logging to the database has been changed. Now, to activate logging, you will need to use the `logEnabled` method, which accepts a Boolean value.

```php
$firebaseSender->logEnabled(bool $enabled = true): void
```

The payload of logs has also been modified to support grouping.

```php
$firebaseSender->setPayload1(?string $payload = null): void
$firebaseSender->setPayload2(?string $payload = null): void
```

### send

Previously, the `send` method returned a Boolean value after sending. Now, this method returns a `SendReport` object, which provides a set of data about the sent messages.

### sendJob

Now `sendJob` accepts one mandatory value and two optional values.

| Value          | Type     | Required | Description                                                    |
| -------------- | -------- | -------- | -------------------------------------------------------------- |
| `$scheduledAt` | `Carbon` | Yes      | Date when notification should be sent                          |
| `$chunkLength` | `int`    | No       | Allows you to split messages into chunks                       |
| `$maxRand`     | `int`    | No       | Adds a random number of seconds to the chunk dispatch schedule |

### Two new exceptions.

Two new exceptions have been added to the `send` and `sendJob` methods, which should be taken into account when sending messages.

- `MissingMessageRecipientException` - Occurs if the group does not have a message recipient.
- `MissingMessageContentException` - Occurs when a message contains only the recipient without any content.

### clear

You can now clear previously sent data to send new notifications without having to re-declare the class.

```php
$firebaseSender->clear(): void
```

## Database

Two new fields have been added to the `firebase_sender_logs` table: `ulid` and `exception`. Therefore, if you have previously used the log, you will need to add these fields to the table by creating a new migration using the code below.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('firebase_sender_logs', 'ulid')) {
            Schema::table('firebase_sender_logs', function (Blueprint $table) {
                $table->ulid('ulid')->unique();
            });
        }

        if (!Schema::hasColumn('firebase_sender_logs', 'exception')) {
            Schema::table('firebase_sender_logs', function (Blueprint $table) {
                $table->text('exception')->nullable()->default(null);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('firebase_sender_logs', 'ulid')) {
            Schema::table('firebase_sender_logs', function (Blueprint $table) {
                $table->dropColumn('ulid');
            });
        }

        if (Schema::hasColumn('firebase_sender_logs', 'exception')) {
            Schema::table('firebase_sender_logs', function (Blueprint $table) {
                $table->dropColumn('exception');
            });
        }
    }
};
```

## Configuration file

Now, in the configuration file, you can enable or disable caching of the Google Access Token. By default, this caching is enabled to reduce the number of token requests.

```php
'cache' => [
    'google_access_token' => true
]
```

You can now also set the number of seconds during which a task can send messages before the timeout expires.

```php
'job' => [
    'send_timeout' => 600
]
```

Now you can automatically delete old log entries using this configuration:

```php
// The number of hours after which log entries will be deleted. Set to null to disable deletion.
'log' => [
    'prune_after' => null,
]
```
