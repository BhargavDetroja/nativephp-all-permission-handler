<?php

require_once __DIR__.'/../src/Enums/Permission.php';
require_once __DIR__.'/../src/Enums/PermissionStatus.php';
require_once __DIR__.'/../src/Enums/ServiceStatus.php';
require_once __DIR__.'/../src/AllPermissionHandler.php';
require_once __DIR__.'/../src/Support/PermissionMetadata.php';

use Nativephp\AllPermissionHandler\AllPermissionHandler;
use Nativephp\AllPermissionHandler\Enums\Permission;
use Nativephp\AllPermissionHandler\Enums\PermissionStatus;
use Nativephp\AllPermissionHandler\Enums\ServiceStatus;
use Nativephp\AllPermissionHandler\Support\PermissionMetadata;

describe('Permission contract', function () {
    it('exposes flutter-aligned permission names', function () {
        expect(Permission::Camera->value)->toBe('camera');
        expect(Permission::AccessMediaLocation->value)->toBe('access_media_location');
        expect(Permission::LocationWhenInUse->value)->toBe('locationWhenInUse');
    });

    it('exposes flutter-aligned permission status integers', function () {
        expect(PermissionStatus::Denied->value)->toBe(0);
        expect(PermissionStatus::Granted->value)->toBe(1);
        expect(PermissionStatus::Restricted->value)->toBe(2);
        expect(PermissionStatus::Limited->value)->toBe(3);
        expect(PermissionStatus::PermanentlyDenied->value)->toBe(4);
        expect(PermissionStatus::Provisional->value)->toBe(5);
    });

    it('exposes flutter-aligned service status integers', function () {
        expect(ServiceStatus::Disabled->value)->toBe(0);
        expect(ServiceStatus::Enabled->value)->toBe(1);
        expect(ServiceStatus::NotApplicable->value)->toBe(2);
    });

    it('falls back safely when native bridge is unavailable', function () {
        $handler = new AllPermissionHandler;

        expect($handler->status(Permission::Camera))->toBe(PermissionStatus::Denied);
        expect($handler->check(Permission::Camera))->toBe(PermissionStatus::Denied);
        expect($handler->request(Permission::Camera))->toBe(PermissionStatus::Denied);
        expect($handler->serviceStatus(Permission::Camera))->toBe(ServiceStatus::NotApplicable);
        expect($handler->shouldShowRequestRationale(Permission::Camera))->toBeFalse();
        expect($handler->openAppSettings())->toBeFalse();
    });

    it('returns deterministic fallback for unsupported names without native bridge', function () {
        $handler = new AllPermissionHandler;

        expect($handler->check('unsupported_permission'))->toBe(PermissionStatus::Denied);
        expect($handler->serviceStatus('unsupported_permission'))->toBe(ServiceStatus::NotApplicable);
    });

    it('supports callback style chaining on request', function () {
        $handler = new AllPermissionHandler;
        $deniedCalled = false;

        $status = $handler
            ->onDeniedCallback(function () use (&$deniedCalled) {
                $deniedCalled = true;
            })
            ->request(Permission::Camera);

        expect($status)->toBe(PermissionStatus::Denied);
        expect($deniedCalled)->toBeTrue();
    });

    it('builds platform metadata from enabled permission config', function () {
        $enabled = PermissionMetadata::normalizeEnabledPermissions([
            Permission::Camera->value,
            Permission::Microphone->value,
            'unknown_permission',
        ], 'none');

        $androidPermissions = PermissionMetadata::androidPermissionsFor($enabled);
        $iosPlist = PermissionMetadata::iosInfoPlistFor($enabled);

        expect($enabled)->toBe([Permission::Camera->value, Permission::Microphone->value]);
        expect($androidPermissions)->toContain('android.permission.CAMERA');
        expect($androidPermissions)->toContain('android.permission.RECORD_AUDIO');
        expect($iosPlist)->toHaveKeys(['NSCameraUsageDescription', 'NSMicrophoneUsageDescription']);
    });

    it('supports preset driven permission expansion', function () {
        $enabled = PermissionMetadata::normalizeEnabledPermissions([], 'camera_only');

        expect($enabled)->toContain(Permission::Camera->value);
        expect($enabled)->not->toContain(Permission::Microphone->value);
    });

    it('includes mediaLibrary and access_media_location in Android metadata', function () {
        $enabled = PermissionMetadata::normalizeEnabledPermissions([
            Permission::MediaLibrary->value,
            Permission::AccessMediaLocation->value,
        ], 'none');

        expect($enabled)->toBe([Permission::MediaLibrary->value, Permission::AccessMediaLocation->value]);

        $android = PermissionMetadata::androidPermissionsFor($enabled);
        expect($android)->toContain('android.permission.READ_MEDIA_IMAGES');
        expect($android)->toContain('android.permission.ACCESS_MEDIA_LOCATION');
    });
});
