<?php

namespace Nativephp\AllPermissionHandler\Enums;

enum Permission: string
{
    case Calendar = 'calendar';
    case Camera = 'camera';
    case Contacts = 'contacts';
    case Location = 'location';
    case LocationAlways = 'locationAlways';
    case LocationWhenInUse = 'locationWhenInUse';
    case MediaLibrary = 'mediaLibrary';
    case Microphone = 'microphone';
    case Phone = 'phone';
    case Photos = 'photos';
    case PhotosAddOnly = 'photosAddOnly';
    case Reminders = 'reminders';
    case Sensors = 'sensors';
    case Sms = 'sms';
    case Speech = 'speech';
    case Storage = 'storage';
    case IgnoreBatteryOptimizations = 'ignoreBatteryOptimizations';
    case Notification = 'notification';
    case AccessMediaLocation = 'access_media_location';
    case ActivityRecognition = 'activity_recognition';
    case Unknown = 'unknown';
    case Bluetooth = 'bluetooth';
    case ManageExternalStorage = 'manageExternalStorage';
    case SystemAlertWindow = 'systemAlertWindow';
    case RequestInstallPackages = 'requestInstallPackages';
    case AppTrackingTransparency = 'appTrackingTransparency';
    case CriticalAlerts = 'criticalAlerts';
    case AccessNotificationPolicy = 'accessNotificationPolicy';
    case BluetoothScan = 'bluetoothScan';
    case BluetoothAdvertise = 'bluetoothAdvertise';
    case BluetoothConnect = 'bluetoothConnect';
    case NearbyWifiDevices = 'nearbyWifiDevices';
    case Videos = 'videos';
    case Audio = 'audio';
    case ScheduleExactAlarm = 'scheduleExactAlarm';
    case SensorsAlways = 'sensorsAlways';
    case CalendarWriteOnly = 'calendarWriteOnly';
    case CalendarFullAccess = 'calendarFullAccess';
    case Assistant = 'assistant';
    case BackgroundRefresh = 'backgroundRefresh';
}
