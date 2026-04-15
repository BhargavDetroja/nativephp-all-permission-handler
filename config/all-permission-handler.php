<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabled Permissions
    |--------------------------------------------------------------------------
    |
    | Safe-by-default: no permissions are enabled automatically.
    | Add only the permission keys your app actually uses.
    |
    | Example:
    | 'enabled_permissions' => ['camera', 'microphone'],
    |
    */
    'enabled_permissions' => [],

    /*
    |--------------------------------------------------------------------------
    | Preset
    |--------------------------------------------------------------------------
    |
    | Optional convenience preset. Supported values:
    | - none (default)
    | - camera_only
    | - media_only
    | - location_only
    | - full
    |
    | Preset values are merged with enabled_permissions.
    |
    */
    'preset' => 'none',

    /*
    |--------------------------------------------------------------------------
    | iOS Usage Description Overrides
    |--------------------------------------------------------------------------
    |
    | Override generated NS*UsageDescription values per Info.plist key.
    | Keep messages specific to your app's actual feature usage.
    |
    */
    'ios_usage_descriptions' => [],
];
