# Migrating to v2

This is a migration instruction from the old v1 to v2 version.

## setHighPriority()

Instead of using the outdated `setHighPriority()` method to set the priority of a notification, you now need to set the priority directly through the corresponding properties when creating `AndroidPush` and `ApnsPush` objects.

For Android notifications, use the `priorityHigh` boolean parameter:

```php
new AndroidPush(
    priorityHigh: true
);
```

For APNs of notifications, set a numerical priority using the priority parameter (where 10 means high priority):

```php
new ApnsPush(
    priority: 10
);
```

## setTimeToLive()

TTL of notifications for Adnroid now needs to be specified in `AndroidPush` objects.

```php
new AndroidPush(
    ttl: 3600
);
```

## setImage()

Links to images are now set in `AndroidPush`, `ApnsPush` objects.

```php
new AndroidPush(
    image: "https://example.com/image.png"
);
```

```php
new ApnsPush(
    image: "https://example.com/image.png"
);
```

## setTokenDevices()

`setTokenDevices()` has been replaced by `setDeviceToken()`.

## Title and body

The methods `setTitle()`, `setTitleLocKey()`, `setBody()`, `setBodyLocKey()` have been replaced with the new `setNotification()` method.

You can learn how to use the `setNotification()` method in the [README.md](https://github.com/mrgarest/laravel-firebase-sender/blob/master/README.md#set-notifications) file.

### Android

The `setAndroidTitleLocKey()` and `setAndroidBodyLocKey()` methods have been replaced with the new `setAndroid()` method.

You can learn how to use the `setAndroid()` method in the [README.md](https://github.com/mrgarest/laravel-firebase-sender/blob/master/README.md#set-notifications-for-android) file.

### APNs

The `setApnsTitleLocKey()` and `setApnsBodyLocKey()` methods have been replaced with the new `setApns()` method.

You can learn how to use the `setApns()` method in the [README.md](https://github.com/mrgarest/laravel-firebase-sender/blob/master/README.md#set-notifications-for-apns) file.


## Database logs

The logging of notification data has been completely redesigned, and the old logging data model is now completely unsupported.

To use the new logging model, you need to manually delete the `firebase_sender_logs` migration from your database and then run the migration:

```
php artisan make:migration
```

Also, the method previously used to write data to the database, `setDatabaseLog()`, is no longer supported. It has been replaced by the new `setLog()` method.