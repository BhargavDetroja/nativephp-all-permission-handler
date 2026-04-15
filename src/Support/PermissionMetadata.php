<?php

namespace Nativephp\AllPermissionHandler\Support;

use Nativephp\AllPermissionHandler\Enums\Permission;

class PermissionMetadata
{
    /**
     * @return array<string, array{android: array<int, string>, ios_info_plist: array<int, string>}>
     */
    public static function permissionMap(): array
    {
        return [
            Permission::Camera->value => [
                'android' => ['android.permission.CAMERA'],
                'ios_info_plist' => ['NSCameraUsageDescription'],
            ],
            Permission::Microphone->value => [
                'android' => ['android.permission.RECORD_AUDIO'],
                'ios_info_plist' => ['NSMicrophoneUsageDescription'],
            ],
            Permission::Photos->value => [
                'android' => [
                    'android.permission.READ_MEDIA_IMAGES',
                    'android.permission.READ_EXTERNAL_STORAGE',
                ],
                'ios_info_plist' => ['NSPhotoLibraryUsageDescription'],
            ],
            Permission::PhotosAddOnly->value => [
                'android' => [],
                'ios_info_plist' => ['NSPhotoLibraryAddUsageDescription'],
            ],
            Permission::MediaLibrary->value => [
                'android' => [
                    'android.permission.READ_MEDIA_IMAGES',
                    'android.permission.READ_MEDIA_VIDEO',
                    'android.permission.READ_MEDIA_AUDIO',
                    'android.permission.READ_EXTERNAL_STORAGE',
                ],
                'ios_info_plist' => ['NSPhotoLibraryUsageDescription'],
            ],
            Permission::Videos->value => [
                'android' => [
                    'android.permission.READ_MEDIA_VIDEO',
                    'android.permission.READ_EXTERNAL_STORAGE',
                ],
                'ios_info_plist' => ['NSPhotoLibraryUsageDescription'],
            ],
            Permission::Audio->value => [
                'android' => [
                    'android.permission.READ_MEDIA_AUDIO',
                    'android.permission.READ_EXTERNAL_STORAGE',
                ],
                'ios_info_plist' => [],
            ],
            Permission::Location->value => [
                'android' => [
                    'android.permission.ACCESS_FINE_LOCATION',
                    'android.permission.ACCESS_COARSE_LOCATION',
                ],
                'ios_info_plist' => ['NSLocationWhenInUseUsageDescription'],
            ],
            Permission::LocationWhenInUse->value => [
                'android' => [
                    'android.permission.ACCESS_FINE_LOCATION',
                    'android.permission.ACCESS_COARSE_LOCATION',
                ],
                'ios_info_plist' => ['NSLocationWhenInUseUsageDescription'],
            ],
            Permission::LocationAlways->value => [
                'android' => ['android.permission.ACCESS_BACKGROUND_LOCATION'],
                'ios_info_plist' => ['NSLocationAlwaysAndWhenInUseUsageDescription'],
            ],
            Permission::Contacts->value => [
                'android' => ['android.permission.READ_CONTACTS', 'android.permission.WRITE_CONTACTS'],
                'ios_info_plist' => ['NSContactsUsageDescription'],
            ],
            Permission::Calendar->value => [
                'android' => ['android.permission.READ_CALENDAR', 'android.permission.WRITE_CALENDAR'],
                'ios_info_plist' => ['NSCalendarsUsageDescription'],
            ],
            Permission::CalendarWriteOnly->value => [
                'android' => ['android.permission.WRITE_CALENDAR'],
                'ios_info_plist' => ['NSCalendarsUsageDescription'],
            ],
            Permission::CalendarFullAccess->value => [
                'android' => ['android.permission.READ_CALENDAR', 'android.permission.WRITE_CALENDAR'],
                'ios_info_plist' => ['NSCalendarsUsageDescription'],
            ],
            Permission::Reminders->value => [
                'android' => [],
                'ios_info_plist' => ['NSRemindersUsageDescription'],
            ],
            Permission::Speech->value => [
                'android' => ['android.permission.RECORD_AUDIO'],
                'ios_info_plist' => ['NSSpeechRecognitionUsageDescription'],
            ],
            Permission::Sensors->value => [
                'android' => ['android.permission.BODY_SENSORS'],
                'ios_info_plist' => ['NSMotionUsageDescription'],
            ],
            Permission::SensorsAlways->value => [
                'android' => ['android.permission.BODY_SENSORS_BACKGROUND'],
                'ios_info_plist' => ['NSMotionUsageDescription'],
            ],
            Permission::Notification->value => [
                'android' => ['android.permission.POST_NOTIFICATIONS'],
                'ios_info_plist' => [],
            ],
            Permission::Phone->value => [
                'android' => ['android.permission.READ_PHONE_STATE'],
                'ios_info_plist' => [],
            ],
            Permission::Sms->value => [
                'android' => [
                    'android.permission.SEND_SMS',
                    'android.permission.RECEIVE_SMS',
                    'android.permission.READ_SMS',
                ],
                'ios_info_plist' => [],
            ],
            Permission::ActivityRecognition->value => [
                'android' => ['android.permission.ACTIVITY_RECOGNITION'],
                'ios_info_plist' => [],
            ],
            Permission::Bluetooth->value => [
                'android' => ['android.permission.BLUETOOTH_CONNECT'],
                'ios_info_plist' => ['NSBluetoothAlwaysUsageDescription', 'NSBluetoothPeripheralUsageDescription'],
            ],
            Permission::BluetoothScan->value => [
                'android' => ['android.permission.BLUETOOTH_SCAN'],
                'ios_info_plist' => ['NSBluetoothAlwaysUsageDescription', 'NSBluetoothPeripheralUsageDescription'],
            ],
            Permission::BluetoothConnect->value => [
                'android' => ['android.permission.BLUETOOTH_CONNECT'],
                'ios_info_plist' => ['NSBluetoothAlwaysUsageDescription', 'NSBluetoothPeripheralUsageDescription'],
            ],
            Permission::BluetoothAdvertise->value => [
                'android' => ['android.permission.BLUETOOTH_ADVERTISE'],
                'ios_info_plist' => ['NSBluetoothAlwaysUsageDescription', 'NSBluetoothPeripheralUsageDescription'],
            ],
            Permission::NearbyWifiDevices->value => [
                'android' => ['android.permission.NEARBY_WIFI_DEVICES'],
                'ios_info_plist' => [],
            ],
            Permission::Storage->value => [
                'android' => ['android.permission.READ_EXTERNAL_STORAGE', 'android.permission.WRITE_EXTERNAL_STORAGE'],
                'ios_info_plist' => [],
            ],
            Permission::AccessMediaLocation->value => [
                'android' => ['android.permission.ACCESS_MEDIA_LOCATION'],
                'ios_info_plist' => [],
            ],
            Permission::ManageExternalStorage->value => [
                'android' => [],
                'ios_info_plist' => [],
            ],
            Permission::SystemAlertWindow->value => [
                'android' => [],
                'ios_info_plist' => [],
            ],
            Permission::RequestInstallPackages->value => [
                'android' => [],
                'ios_info_plist' => [],
            ],
            Permission::IgnoreBatteryOptimizations->value => [
                'android' => [],
                'ios_info_plist' => [],
            ],
            Permission::ScheduleExactAlarm->value => [
                'android' => [],
                'ios_info_plist' => [],
            ],
            Permission::AccessNotificationPolicy->value => [
                'android' => [],
                'ios_info_plist' => [],
            ],
            Permission::AppTrackingTransparency->value => [
                'android' => [],
                'ios_info_plist' => ['NSUserTrackingUsageDescription'],
            ],
            Permission::CriticalAlerts->value => [
                'android' => [],
                'ios_info_plist' => [],
            ],
            Permission::Assistant->value => [
                'android' => [],
                'ios_info_plist' => [],
            ],
            Permission::BackgroundRefresh->value => [
                'android' => [],
                'ios_info_plist' => [],
            ],
            Permission::Unknown->value => [
                'android' => [],
                'ios_info_plist' => [],
            ],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function presets(): array
    {
        return [
            'none' => [],
            'camera_only' => [Permission::Camera->value],
            'media_only' => [Permission::Photos->value, Permission::Videos->value, Permission::Audio->value],
            'location_only' => [Permission::LocationWhenInUse->value],
            'full' => array_values(array_map(
                static fn (Permission $permission): string => $permission->value,
                Permission::cases()
            )),
        ];
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    public static function normalizeEnabledPermissions(array $permissions, ?string $preset = null): array
    {
        $map = self::permissionMap();
        $presetPermissions = self::presets()[$preset ?? 'none'] ?? [];
        $requested = array_values(array_unique(array_merge($presetPermissions, $permissions)));

        if (in_array('*', $requested, true) || in_array('all', $requested, true)) {
            return array_values(array_keys($map));
        }

        return array_values(array_filter(
            $requested,
            static fn (string $permission): bool => array_key_exists($permission, $map)
        ));
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    public static function androidPermissionsFor(array $permissions): array
    {
        $map = self::permissionMap();
        $result = [];

        foreach ($permissions as $permission) {
            foreach ($map[$permission]['android'] ?? [] as $androidPermission) {
                $result[$androidPermission] = true;
            }
        }

        return array_values(array_keys($result));
    }

    /**
     * @param  array<int, string>  $permissions
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    public static function iosInfoPlistFor(array $permissions, array $overrides = []): array
    {
        $map = self::permissionMap();
        $result = [];

        foreach ($permissions as $permission) {
            foreach ($map[$permission]['ios_info_plist'] ?? [] as $plistKey) {
                $result[$plistKey] = $overrides[$plistKey] ?? self::defaultIosUsageDescriptions()[$plistKey] ?? self::fallbackUsageDescription($plistKey);
            }
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public static function defaultIosUsageDescriptions(): array
    {
        return [
            'NSCameraUsageDescription' => 'This app needs camera access to provide camera-based features.',
            'NSMicrophoneUsageDescription' => 'This app needs microphone access to provide voice and audio features.',
            'NSPhotoLibraryUsageDescription' => 'This app needs photo library access to let you select media.',
            'NSPhotoLibraryAddUsageDescription' => 'This app needs access to save photos to your library.',
            'NSLocationWhenInUseUsageDescription' => 'This app needs location access while in use.',
            'NSLocationAlwaysAndWhenInUseUsageDescription' => 'This app needs background location access for continuous location features.',
            'NSContactsUsageDescription' => 'This app needs contact access to support contact-related features.',
            'NSCalendarsUsageDescription' => 'This app needs calendar access to support event features.',
            'NSRemindersUsageDescription' => 'This app needs reminders access.',
            'NSSpeechRecognitionUsageDescription' => 'This app needs speech recognition access.',
            'NSMotionUsageDescription' => 'This app needs motion sensor access.',
            'NSUserTrackingUsageDescription' => 'This app uses tracking permission where required for functionality.',
            'NSBluetoothAlwaysUsageDescription' => 'This app needs Bluetooth access for nearby device features.',
            'NSBluetoothPeripheralUsageDescription' => 'This app needs Bluetooth access for nearby device features.',
        ];
    }

    protected static function fallbackUsageDescription(string $plistKey): string
    {
        return "This app requires {$plistKey} for requested functionality.";
    }
}
