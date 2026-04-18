# Changelog

All notable changes to `bhargavdetroja/nativephp-all-permission-handle` are documented in this file.

The format follows Keep a Changelog principles and semantic versioning.

## [Unreleased]

## [1.0.1] - 2026-04-18

### Fixed
- Android: map `mediaLibrary` and `access_media_location` in `AllPermissionHandlerFunctions.kt` so they match PHP `PermissionMetadata` (previously these keys always resolved to no Android permissions and stayed denied).

### Changed
- README: document NativePHP Mobile prerequisites, correct install order (`nativephp-plugins-provider` before `native:plugin:register`), verification command, and a troubleshooting table for common crashes and “missing class” errors.
- README: add a minimal copy-paste camera permission demo (route, Livewire, and JS).

## [1.0.0] - 2026-04-15

### Added
- Safe-by-default package config with explicit `enabled_permissions` controls.
- Preset support (`none`, `camera_only`, `media_only`, `location_only`, `full`) for quick setup.
- Centralized permission metadata mapping for Android permissions and iOS `Info.plist` keys.
- Build-time generated metadata artifacts for Android and iOS permission output auditing.
- Non-breaking `status()` alias in PHP and JavaScript for ergonomic parity with check flows.
- JavaScript status normalizers for stable fallback values across bridge response shapes.
- `shouldShowRequestRationale` bridge/API support (Android semantics, iOS false fallback).
- Callback-style request hooks for PHP and JavaScript request flows.
- Flutter-aligned permission enums and status contracts for PHP.
- Bridge methods for check/request/requestMultiple/serviceStatus/openAppSettings.
- Android and iOS native bridge implementations for core permission flows.
- Frontend JavaScript bridge helpers for all public methods.
- Plugin validation and contract tests with Pest.

### Changed
- Plugin manifest permission defaults are now minimal (`android.permissions: []`, `ios.info_plist: {}`).
- Service provider now publishes and merges package config (`all-permission-handler-config` tag).
- Build hook now derives platform permission metadata from app config.
- README restructured with API matrix, status mapping, and clearer production release gate.
- README now includes safe-by-default setup, presets, and store-compliance guidance.
- iOS `serviceStatus(notification)` now reflects current notification authorization state.
- Android service status semantics refined for phone and bluetooth families.
- iOS added permission handling for app tracking, bluetooth, background refresh, media library, and critical alerts.
- Native source layout aligned to NativePHP discovery expectations.

### Notes
- Some permissions are OS/version dependent and can return fallback statuses.
- Certain Android permissions use settings flows rather than runtime popups.
