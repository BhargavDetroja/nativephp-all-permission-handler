<?php

namespace Nativephp\AllPermissionHandler\Facades;

use Illuminate\Support\Facades\Facade;
use Nativephp\AllPermissionHandler\Enums\Permission;
use Nativephp\AllPermissionHandler\Enums\PermissionStatus;
use Nativephp\AllPermissionHandler\Enums\ServiceStatus;

/**
 * @method static PermissionStatus status(Permission|string $permission)
 * @method static PermissionStatus check(Permission|string $permission)
 * @method static PermissionStatus request(Permission|string $permission)
 * @method static array<string, PermissionStatus> requestMultiple(array $permissions)
 * @method static ServiceStatus serviceStatus(Permission|string $permission)
 * @method static bool openAppSettings()
 * @method static bool shouldShowRequestRationale(Permission|string $permission)
 * @method static \Nativephp\AllPermissionHandler\AllPermissionHandler onDeniedCallback(?callable $callback)
 * @method static \Nativephp\AllPermissionHandler\AllPermissionHandler onGrantedCallback(?callable $callback)
 * @method static \Nativephp\AllPermissionHandler\AllPermissionHandler onPermanentlyDeniedCallback(?callable $callback)
 * @method static \Nativephp\AllPermissionHandler\AllPermissionHandler onRestrictedCallback(?callable $callback)
 * @method static \Nativephp\AllPermissionHandler\AllPermissionHandler onLimitedCallback(?callable $callback)
 * @method static \Nativephp\AllPermissionHandler\AllPermissionHandler onProvisionalCallback(?callable $callback)
 *
 * @see \Nativephp\AllPermissionHandler\AllPermissionHandler
 */
class AllPermissionHandler extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Nativephp\AllPermissionHandler\AllPermissionHandler::class;
    }
}
