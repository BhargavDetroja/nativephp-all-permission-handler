<?php

/**
 * Plugin validation tests for AllPermissionHandler.
 *
 * Run with: ./vendor/bin/pest
 */
beforeEach(function () {
    $this->pluginPath = dirname(__DIR__);
    $this->manifestPath = $this->pluginPath.'/nativephp.json';
});

describe('Plugin Manifest', function () {
    it('has a valid nativephp.json file', function () {
        expect(file_exists($this->manifestPath))->toBeTrue();

        $content = file_get_contents($this->manifestPath);
        $manifest = json_decode($content, true);

        expect(json_last_error())->toBe(JSON_ERROR_NONE);
    });

    it('has required fields', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest)->toHaveKeys(['namespace', 'bridge_functions']);
        expect($manifest['namespace'])->toBe('AllPermissionHandler');
    });

    it('has valid bridge functions', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest['bridge_functions'])->toBeArray();

        foreach ($manifest['bridge_functions'] as $function) {
            expect($function)->toHaveKeys(['name']);
            expect(isset($function['android']) || isset($function['ios']))->toBeTrue();
        }

        $names = array_map(fn (array $function): string => $function['name'], $manifest['bridge_functions']);
        expect($names)->toBe([
            'AllPermissionHandler.Check',
            'AllPermissionHandler.Request',
            'AllPermissionHandler.RequestMultiple',
            'AllPermissionHandler.ServiceStatus',
            'AllPermissionHandler.OpenAppSettings',
            'AllPermissionHandler.ShouldShowRequestRationale',
        ]);
    });

    it('has valid marketplace metadata', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        // Optional but recommended for marketplace
        if (isset($manifest['keywords'])) {
            expect($manifest['keywords'])->toBeArray();
        }

        if (isset($manifest['category'])) {
            expect($manifest['category'])->toBeString();
        }

        if (isset($manifest['platforms'])) {
            expect($manifest['platforms'])->toBeArray();
            foreach ($manifest['platforms'] as $platform) {
                expect($platform)->toBeIn(['android', 'ios']);
            }
        }

        // Package-level metadata should live in composer.json for NativePHP v3.
        expect(isset($manifest['name']))->toBeFalse();
        expect(isset($manifest['version']))->toBeFalse();
        expect(isset($manifest['description']))->toBeFalse();
        expect(isset($manifest['service_provider']))->toBeFalse();
    });
});

describe('Native Code', function () {
    it('has Android Kotlin file', function () {
        $kotlinFile = $this->pluginPath.'/resources/android/src/AllPermissionHandlerFunctions.kt';

        expect(file_exists($kotlinFile))->toBeTrue();

        $content = file_get_contents($kotlinFile);
        expect($content)->toContain('package com.nativephp.plugins.all_permission_handler');
        expect($content)->toContain('object AllPermissionHandlerFunctions');
        expect($content)->toContain('BridgeFunction');
    });

    it('has iOS Swift file', function () {
        $swiftFile = $this->pluginPath.'/resources/ios/Sources/AllPermissionHandlerFunctions.swift';

        expect(file_exists($swiftFile))->toBeTrue();

        $content = file_get_contents($swiftFile);
        expect($content)->toContain('enum AllPermissionHandlerFunctions');
        expect($content)->toContain('BridgeFunction');
    });

    it('uses NativePHP expected native source directories', function () {
        expect(is_dir($this->pluginPath.'/resources/android/src'))->toBeTrue();
        expect(is_dir($this->pluginPath.'/resources/ios/Sources'))->toBeTrue();
    });

    it('has matching bridge function classes in native code', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        $kotlinFile = $this->pluginPath.'/resources/android/src/AllPermissionHandlerFunctions.kt';
        $swiftFile = $this->pluginPath.'/resources/ios/Sources/AllPermissionHandlerFunctions.swift';

        $kotlinContent = file_get_contents($kotlinFile);
        $swiftContent = file_get_contents($swiftFile);

        foreach ($manifest['bridge_functions'] as $function) {
            // Extract class name from the function reference
            if (isset($function['android'])) {
                $parts = explode('.', $function['android']);
                $className = end($parts);
                expect($kotlinContent)->toContain("class {$className}");
            }

            if (isset($function['ios'])) {
                $parts = explode('.', $function['ios']);
                $className = end($parts);
                expect($swiftContent)->toContain("class {$className}");
            }
        }
    });
});

describe('PHP Classes', function () {
    it('has service provider', function () {
        $file = $this->pluginPath.'/src/AllPermissionHandlerServiceProvider.php';
        expect(file_exists($file))->toBeTrue();

        $content = file_get_contents($file);
        expect($content)->toContain('namespace Nativephp\AllPermissionHandler');
        expect($content)->toContain('class AllPermissionHandlerServiceProvider');
        expect($content)->toContain('mergeConfigFrom');
        expect($content)->toContain('all-permission-handler-config');
    });

    it('has facade', function () {
        $file = $this->pluginPath.'/src/Facades/AllPermissionHandler.php';
        expect(file_exists($file))->toBeTrue();

        $content = file_get_contents($file);
        expect($content)->toContain('namespace Nativephp\AllPermissionHandler\Facades');
        expect($content)->toContain('class AllPermissionHandler extends Facade');
        expect($content)->toContain('@method static PermissionStatus status(Permission|string $permission)');
    });

    it('has main implementation class', function () {
        $file = $this->pluginPath.'/src/AllPermissionHandler.php';
        expect(file_exists($file))->toBeTrue();

        $content = file_get_contents($file);
        expect($content)->toContain('namespace Nativephp\AllPermissionHandler');
        expect($content)->toContain('class AllPermissionHandler');
    });
});

describe('Safe-by-default Configuration', function () {
    it('has publishable configuration file', function () {
        $configFile = $this->pluginPath.'/config/all-permission-handler.php';

        expect(file_exists($configFile))->toBeTrue();

        $content = file_get_contents($configFile);
        expect($content)->toContain("'enabled_permissions' => []");
        expect($content)->toContain("'preset' => 'none'");
    });

    it('uses minimal default permissions in manifest', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest['android']['permissions'] ?? null)->toBeArray()->toBeEmpty();
        expect($manifest['ios']['info_plist'] ?? null)->toBeArray()->toBeEmpty();
    });
});

describe('JavaScript Bridge API', function () {
    it('exports expected methods including status alias', function () {
        $file = $this->pluginPath.'/resources/js/allPermissionHandler.js';
        expect(file_exists($file))->toBeTrue();

        $content = file_get_contents($file);
        expect($content)->toContain('export async function status(permission)');
        expect($content)->toContain('export async function check(permission)');
        expect($content)->toContain('export async function request(permission)');
        expect($content)->toContain('export async function requestMultiple(permissions = [])');
        expect($content)->toContain('export async function serviceStatus(permission)');
        expect($content)->toContain('export async function openAppSettings()');
        expect($content)->toContain('export async function shouldShowRequestRationale(permission)');
        expect($content)->toContain('export function withCallbacks(permission)');
    });
});

describe('Composer Configuration', function () {
    it('has valid composer.json', function () {
        $composerPath = $this->pluginPath.'/composer.json';
        expect(file_exists($composerPath))->toBeTrue();

        $content = file_get_contents($composerPath);
        $composer = json_decode($content, true);

        expect(json_last_error())->toBe(JSON_ERROR_NONE);
        expect($composer['type'])->toBe('nativephp-plugin');
        expect($composer['extra']['nativephp']['manifest'])->toBe('nativephp.json');
    });
});

describe('Lifecycle Hooks', function () {
    it('has valid hooks configuration', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        if (isset($manifest['hooks'])) {
            expect($manifest['hooks'])->toBeArray();

            $validHooks = ['pre_compile', 'post_compile', 'copy_assets', 'post_build'];
            foreach (array_keys($manifest['hooks']) as $hook) {
                expect($hook)->toBeIn($validHooks);
            }
        }
    });

    it('has copy_assets hook command', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest['hooks']['copy_assets'] ?? null)->not->toBeNull();

        $commandFile = $this->pluginPath.'/src/Commands/CopyAssetsCommand.php';
        expect(file_exists($commandFile))->toBeTrue();
    });

    it('copy_assets command extends NativePluginHookCommand', function () {
        $commandFile = $this->pluginPath.'/src/Commands/CopyAssetsCommand.php';
        $content = file_get_contents($commandFile);

        expect($content)->toContain('extends NativePluginHookCommand');
        expect($content)->toContain('use Native\Mobile\Plugins\Commands\NativePluginHookCommand');
    });

    it('copy_assets command has correct signature', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);
        $expectedSignature = $manifest['hooks']['copy_assets'];

        $commandFile = $this->pluginPath.'/src/Commands/CopyAssetsCommand.php';
        $content = file_get_contents($commandFile);

        expect($content)->toContain('$signature = \''.$expectedSignature.'\'');
    });

    it('copy_assets command has platform-specific methods', function () {
        $commandFile = $this->pluginPath.'/src/Commands/CopyAssetsCommand.php';
        $content = file_get_contents($commandFile);

        // Should check for platform
        expect($content)->toContain('$this->isAndroid()');
        expect($content)->toContain('$this->isIos()');
        expect($content)->toContain('PermissionMetadata::normalizeEnabledPermissions');
        expect($content)->toContain('all-permission-handler.generated.');
        expect($content)->toContain('.json');
        expect($content)->toContain('IosInfoPlistMerger');
        expect($content)->toContain('mergeIosInfoPlistIntoNativeProject');
    });

    it('has valid assets configuration', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        // Assets are at top level with android/ios nested inside
        if (isset($manifest['assets'])) {
            expect($manifest['assets'])->toBeArray();

            if (isset($manifest['assets']['android'])) {
                expect($manifest['assets']['android'])->toBeArray();
            }

            if (isset($manifest['assets']['ios'])) {
                expect($manifest['assets']['ios'])->toBeArray();
            }
        }
    });
});
