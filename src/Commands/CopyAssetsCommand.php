<?php

namespace Nativephp\AllPermissionHandler\Commands;

use Native\Mobile\Plugins\Commands\NativePluginHookCommand;
use Nativephp\AllPermissionHandler\Support\IosInfoPlistMerger;
use Nativephp\AllPermissionHandler\Support\PermissionMetadata;

class CopyAssetsCommand extends NativePluginHookCommand
{
    protected $signature = 'nativephp:all-permission-handler:copy-assets';

    protected $description = 'Generate safe-by-default permission metadata for active platform';

    public function handle(): int
    {
        /** @var array<int, string> $enabled */
        $enabled = (array) config('all-permission-handler.enabled_permissions', []);
        $preset = config('all-permission-handler.preset', 'none');
        /** @var array<string, string> $iosOverrides */
        $iosOverrides = (array) config('all-permission-handler.ios_usage_descriptions', []);

        $normalizedPermissions = PermissionMetadata::normalizeEnabledPermissions($enabled, is_string($preset) ? $preset : null);
        $androidPermissions = PermissionMetadata::androidPermissionsFor($normalizedPermissions);
        $iosInfoPlist = PermissionMetadata::iosInfoPlistFor($normalizedPermissions, $iosOverrides);

        if ($this->isAndroid()) {
            $this->copyAndroidAssets($normalizedPermissions, $androidPermissions);
        }

        if ($this->isIos()) {
            $this->copyIosAssets($normalizedPermissions, $iosInfoPlist);
        }

        if (empty($normalizedPermissions)) {
            $this->warn('No permissions enabled. Plugin is running in safe-by-default mode.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $normalizedPermissions
     * @param  array<int, string>  $androidPermissions
     */
    protected function copyAndroidAssets(array $normalizedPermissions, array $androidPermissions): void
    {
        $payload = [
            'enabled_permissions' => $normalizedPermissions,
            'android_permissions' => $androidPermissions,
        ];

        $this->writeGeneratedMetadata('android', $payload);

        $this->info('Generated Android permission metadata for AllPermissionHandler.');
    }

    /**
     * @param  array<int, string>  $normalizedPermissions
     * @param  array<string, string>  $iosInfoPlist
     */
    protected function copyIosAssets(array $normalizedPermissions, array $iosInfoPlist): void
    {
        $payload = [
            'enabled_permissions' => $normalizedPermissions,
            'ios_info_plist' => $iosInfoPlist,
        ];

        $this->writeGeneratedMetadata('ios', $payload);

        $this->info('Generated iOS Info.plist metadata for AllPermissionHandler.');

        $this->mergeIosInfoPlistIntoNativeProject($iosInfoPlist);
    }

    /**
     * NativePHP reads static keys from nativephp.json only; generated JSON is not applied automatically.
     * Merge config-derived usage strings into the real Xcode Info.plists next to the iOS build path.
     *
     * @param  array<string, string>  $iosInfoPlist
     */
    protected function mergeIosInfoPlistIntoNativeProject(array $iosInfoPlist): void
    {
        if ($iosInfoPlist === []) {
            return;
        }

        $base = rtrim($this->buildPath(), '/');
        $targets = [
            $base.'/NativePHP-simulator-Info.plist',
            $base.'/NativePHP/Info.plist',
        ];

        foreach ($targets as $plistPath) {
            if (! is_file($plistPath)) {
                continue;
            }

            if (! IosInfoPlistMerger::mergeFile($plistPath, $iosInfoPlist)) {
                $this->warn("Could not merge usage descriptions into: {$plistPath}");

                continue;
            }

            $this->info("Merged usage descriptions into: {$plistPath}");
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function writeGeneratedMetadata(string $platform, array $payload): void
    {
        $outputPath = rtrim($this->buildPath(), '/')."/all-permission-handler.generated.{$platform}.json";
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (! is_string($encoded)) {
            $this->warn('Failed to encode generated permission metadata.');

            return;
        }

        file_put_contents($outputPath, $encoded.PHP_EOL);
    }
}
