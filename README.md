# AllPermissionHandler Plugin for NativePHP Mobile

Native permission handling for iOS and Android in NativePHP Mobile applications.

## Overview

This plugin provides a Flutter `permission_handler`-aligned API surface for:

- checking permission status
- requesting one or many permissions
- checking service status where relevant
- opening app settings

## Prerequisites

- A Laravel app with **[NativePHP Mobile v3](https://nativephp.com/docs/mobile/3/getting-started/installation)** installed and scaffolded (`nativephp/mobile` in `composer.json`, `php artisan native:install` or equivalent per docs).
- Without this, Composer may install the plugin, but **native bridge code will not compile** and you will see errors like `NativeServiceProvider not found` or missing native classes.

## Installation (order matters)

Run these commands in your **host Laravel + NativePHP Mobile** project:

```bash
composer require bhargavdetroja/nativephp-all-permission-handle

# 1) Creates app/Providers/NativeServiceProvider.php (required before register)
php artisan vendor:publish --tag=nativephp-plugins-provider

# 2) Registers this plugin so Swift/Kotlin is included in native builds
php artisan native:plugin:register bhargavdetroja/nativephp-all-permission-handle

# 3) App-level permission config (safe-by-default: enable what you use)
php artisan vendor:publish --tag=all-permission-handler-config
```

Verify the plugin is visible:

```bash
php artisan native:plugin:list
```

Then **rebuild / run** the native app (e.g. `php artisan native:run`) so generated permission metadata and native code are applied.

### Troubleshooting

| Symptom | Likely cause | What to do |
| -------- | ------------- | ---------- |
| `NativeServiceProvider not found` | Skipped step 1 | Run `vendor:publish --tag=nativephp-plugins-provider`, then `native:plugin:register` again. |
| `Class ... NativePluginHookCommand not found` | `nativephp/mobile` missing or old | `composer require nativephp/mobile:^3.0`, run `composer update`, clear caches. |
| iOS crash: `TCC` / `NSCameraUsageDescription` (or other `NS*UsageDescription`) | Permission used in code but not enabled / no usage string | Set `enabled_permissions` and `ios_usage_descriptions` in `config/all-permission-handler.php`, rebuild iOS; delete old simulator install if needed. |
| Permission always `denied` on device | Plugin not registered or stale build | Confirm `native:plugin:list`, then clean rebuild. On Android, ensure the permission key matches the PHP enum (e.g. `mediaLibrary`, `access_media_location`). |
| Works on web/CLI, not in app | `nativephp_call` only exists in the native shell | Test inside NativePHP mobile runtime, not `php artisan serve` only. |

## Safe-by-default permission setup

This plugin does **not** declare mobile permissions by default.  
You must explicitly enable the permissions your app needs in `config/all-permission-handler.php`.

```php
return [
    'enabled_permissions' => [
        'camera',
        'microphone',
    ],
    'preset' => 'none',
    'ios_usage_descriptions' => [
        'NSCameraUsageDescription' => 'Use camera to scan receipts for expense tracking.',
        'NSMicrophoneUsageDescription' => 'Use microphone for voice note attachments.',
    ],
];
```

### Presets

Available `preset` values:

- `none` (default)
- `camera_only`
- `media_only`
- `location_only`
- `full`

`preset` values are merged with `enabled_permissions`.

## Minimal camera demo (copy-paste)

Do the [installation](#installation-order-matters) steps once, then set `config/all-permission-handler.php` to at least:

```php
<?php

return [
    'enabled_permissions' => ['camera'],
    'preset' => 'none',
    'ios_usage_descriptions' => [
        'NSCameraUsageDescription' => 'This screen needs the camera for the demo.',
    ],
];
```

Rebuild the native app after changing config (`php artisan native:run` or your usual iOS/Android flow).

### Option A — one route (fastest)

`routes/web.php`:

```php
use Illuminate\Support\Facades\Route;
use Nativephp\AllPermissionHandler\Facades\AllPermissionHandler;
use Nativephp\AllPermissionHandler\Enums\Permission;

Route::get('/demo/camera', function () {
    $status = AllPermissionHandler::request(Permission::Camera);

    return response('Camera status: '.$status->name, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
});
```

Open `/demo/camera` in the **NativePHP mobile app** (not only the browser). You should get the system camera prompt.

### Option B — Livewire button

`app/Livewire/CameraDemo.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use Nativephp\AllPermissionHandler\Facades\AllPermissionHandler;
use Nativephp\AllPermissionHandler\Enums\Permission;

class CameraDemo extends Component
{
    public string $cameraStatus = 'not asked';

    public function requestCamera(): void
    {
        $this->cameraStatus = AllPermissionHandler::request(Permission::Camera)->name;
    }

    public function render()
    {
        return view('livewire.camera-demo');
    }
}
```

`resources/views/livewire/camera-demo.blade.php`:

```blade
<div class="p-4">
    <button type="button" wire:click="requestCamera"
        class="rounded-lg bg-neutral-900 px-4 py-2 text-white active:opacity-80">
        Request camera
    </button>
    <p class="mt-3 text-sm text-neutral-600">Status: <strong>{{ $cameraStatus }}</strong></p>
</div>
```

`routes/web.php`:

```php
use App\Livewire\CameraDemo;
use Illuminate\Support\Facades\Route;

Route::get('/demo/camera', CameraDemo::class);
```

### Option C — JavaScript (Inertia / Vite)

```javascript
import { request } from '@nativephp/all-permission-handler';

// e.g. inside a button click handler
const code = await request('camera');
console.log('camera status code', code); // 0 = denied, 1 = granted, …
```

## Usage

### PHP (Livewire/Blade)

```php
use Nativephp\AllPermissionHandler\Facades\AllPermissionHandler;
use Nativephp\AllPermissionHandler\Enums\Permission;

$status = AllPermissionHandler::status(Permission::Camera); // Alias of check()
$checked = AllPermissionHandler::check(Permission::Camera);
$requested = AllPermissionHandler::request(Permission::Camera);
$multiple = AllPermissionHandler::requestMultiple([
    Permission::Camera,
    Permission::Microphone,
]);
$service = AllPermissionHandler::serviceStatus(Permission::LocationWhenInUse);
$shouldShowRationale = AllPermissionHandler::shouldShowRequestRationale(Permission::Camera);

$statusWithCallbacks = AllPermissionHandler::onDeniedCallback(fn () => logger()->warning('camera denied'))
    ->onGrantedCallback(fn () => logger()->info('camera granted'))
    ->request(Permission::Camera);
$opened = AllPermissionHandler::openAppSettings();
```

### JavaScript (Inertia / Vue / React)

```javascript
import {
  status,
  check,
  request,
  requestMultiple,
  serviceStatus,
  shouldShowRequestRationale,
  withCallbacks,
  openAppSettings,
} from '@nativephp/all-permission-handler';

const cameraStatus = await status('camera'); // Alias of check()
const cameraChecked = await check('camera');
const cameraRequested = await request('camera');
const many = await requestMultiple(['camera', 'microphone']);
const locationService = await serviceStatus('locationWhenInUse');
const rationale = await shouldShowRequestRationale('camera');
const callbackStatus = await withCallbacks('camera')
  .onDeniedCallback(() => console.warn('camera denied'))
  .onGrantedCallback(() => console.info('camera granted'))
  .request();
const opened = await openAppSettings();
```

## API Methods


| Method                                   | Input        | Output                 | Notes                                                        |
| ---------------------------------------- | ------------ | ---------------------- | ------------------------------------------------------------ |
| `status(permission)`                     | Permission   | string                 | `PermissionStatus` / `number`                                |
| `check(permission)`                      | Permission   | string                 | `PermissionStatus` / `number`                                |
| `request(permission)`                    | Permission   | string                 | `PermissionStatus` / `number`                                |
| `requestMultiple(permissions)`           | array<string | Permission>`/`string[] | `array<string, PermissionStatus>` / `Record<string, number>` |
| `serviceStatus(permission)`              | Permission   | string                 | `ServiceStatus` / `number`                                   |
| `shouldShowRequestRationale(permission)` | Permission   | string`/`string        | `bool` / `boolean`                                           |
| `openAppSettings()`                      | none         | `bool` / `boolean`     | Opens app settings screen                                    |
| `withCallbacks(permission)`              | `string`     | request builder        | JS callback chain helper around `request`                    |


## All Supported Permissions

Use `Permission::<EnumName>` in PHP or the matching string value in JavaScript.

| PHP Enum | JS/String value |
| --- | --- |
| `Permission::Calendar` | `calendar` |
| `Permission::Camera` | `camera` |
| `Permission::Contacts` | `contacts` |
| `Permission::Location` | `location` |
| `Permission::LocationAlways` | `locationAlways` |
| `Permission::LocationWhenInUse` | `locationWhenInUse` |
| `Permission::MediaLibrary` | `mediaLibrary` |
| `Permission::Microphone` | `microphone` |
| `Permission::Phone` | `phone` |
| `Permission::Photos` | `photos` |
| `Permission::PhotosAddOnly` | `photosAddOnly` |
| `Permission::Reminders` | `reminders` |
| `Permission::Sensors` | `sensors` |
| `Permission::Sms` | `sms` |
| `Permission::Speech` | `speech` |
| `Permission::Storage` | `storage` |
| `Permission::IgnoreBatteryOptimizations` | `ignoreBatteryOptimizations` |
| `Permission::Notification` | `notification` |
| `Permission::AccessMediaLocation` | `access_media_location` |
| `Permission::ActivityRecognition` | `activity_recognition` |
| `Permission::Unknown` | `unknown` |
| `Permission::Bluetooth` | `bluetooth` |
| `Permission::ManageExternalStorage` | `manageExternalStorage` |
| `Permission::SystemAlertWindow` | `systemAlertWindow` |
| `Permission::RequestInstallPackages` | `requestInstallPackages` |
| `Permission::AppTrackingTransparency` | `appTrackingTransparency` |
| `Permission::CriticalAlerts` | `criticalAlerts` |
| `Permission::AccessNotificationPolicy` | `accessNotificationPolicy` |
| `Permission::BluetoothScan` | `bluetoothScan` |
| `Permission::BluetoothAdvertise` | `bluetoothAdvertise` |
| `Permission::BluetoothConnect` | `bluetoothConnect` |
| `Permission::NearbyWifiDevices` | `nearbyWifiDevices` |
| `Permission::Videos` | `videos` |
| `Permission::Audio` | `audio` |
| `Permission::ScheduleExactAlarm` | `scheduleExactAlarm` |
| `Permission::SensorsAlways` | `sensorsAlways` |
| `Permission::CalendarWriteOnly` | `calendarWriteOnly` |
| `Permission::CalendarFullAccess` | `calendarFullAccess` |
| `Permission::Assistant` | `assistant` |
| `Permission::BackgroundRefresh` | `backgroundRefresh` |

## Generated metadata from config

At build time, the plugin hook computes metadata from your enabled permissions and writes:

- `all-permission-handler.generated.android.json`
- `all-permission-handler.generated.ios.json`

These artifacts are generated in the native build path and are intended to make permission output auditable in CI/release workflows.

## Store compliance guidance

- Enable only permissions used by real, user-facing features.
- Keep iOS usage descriptions specific to your app behavior (avoid generic text).
- Avoid enabling sensitive Android permissions (`sms`, background location, etc.) unless required.
- Re-verify permission lists before every App Store / Play Store submission.

### Example presets for common apps

**Camera-only app**

```php
'preset' => 'camera_only',
'enabled_permissions' => [],
```

**Location-only app**

```php
'preset' => 'location_only',
'enabled_permissions' => [],
```

**Custom mixed set**

```php
'preset' => 'none',
'enabled_permissions' => ['camera', 'photos', 'notification'],
```

## Status Code Mapping

### Permission status


| Code | Meaning           |
| ---- | ----------------- |
| `0`  | denied            |
| `1`  | granted           |
| `2`  | restricted        |
| `3`  | limited           |
| `4`  | permanentlyDenied |
| `5`  | provisional       |


### Service status


| Code | Meaning       |
| ---- | ------------- |
| `0`  | disabled      |
| `1`  | enabled       |
| `2`  | notApplicable |


## Platform Behavior


| Group                         | iOS                   | Android                               | Notes                                         |
| ----------------------------- | --------------------- | ------------------------------------- | --------------------------------------------- |
| Camera, Microphone            | Yes                   | Yes                                   | Runtime popup                                 |
| Location (when-in-use/always) | Yes                   | Yes                                   | May require settings after denial             |
| Photos / Media                | Yes                   | Yes                                   | Android API-level dependent media permissions |
| Contacts, Calendar, Reminders | Yes                   | Yes (reminders iOS-only)              | Calendar split supported                      |
| Notifications                 | Yes                   | Yes                                   | Android 13+ runtime popup                     |
| Speech                        | Yes                   | Yes                                   | Android maps to microphone                    |
| Sensors / Activity            | Partial               | Yes                                   | API-level/hardware dependent                  |
| Special system permissions    | Limited               | Yes                                   | Android uses settings flows                   |
| Service status                | Location/Notification | Location/Notification/Phone/Bluetooth | Others return notApplicable                   |


### Settings-based flows

Android system-level permissions open settings pages instead of runtime popups:

- `manageExternalStorage`
- `systemAlertWindow`
- `requestInstallPackages`
- `ignoreBatteryOptimizations`
- `scheduleExactAlarm`
- `accessNotificationPolicy`

iOS may stop showing prompts after denial; users might need to grant access from settings.

## Events

Current plugin release does not emit dedicated permission result events.  
Primary interaction is request/response via bridge methods.

## Development and verification

```bash
# Validate plugin structure and manifest
php artisan native:plugin:validate packages/nativephp/all-permission-handler --no-interaction

# Run plugin tests
php vendor/bin/pest --compact packages/nativephp/all-permission-handler/tests
```

## Semantic versioning policy

- `MAJOR`: breaking API/contract or behavior changes.
- `MINOR`: new permission features, supported flows, non-breaking additions.
- `PATCH`: bug fixes, docs updates, test/validator hardening, internal refactors.

## Production release gate (pass/fail)

- Plugin validator passes
- Plugin tests pass
- iOS real-device matrix pass (grant/deny/retry/settings-return)
- Android real-device matrix pass (grant/deny/dont-ask-again/settings-return)
- Release-mode smoke validation completed for iOS + Android
- Changelog updated for release

## Changelog

See `[CHANGELOG.md](./CHANGELOG.md)` for release history.

## License

MIT