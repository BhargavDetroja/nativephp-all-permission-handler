<?php

namespace Nativephp\AllPermissionHandler;

use Nativephp\AllPermissionHandler\Enums\Permission;
use Nativephp\AllPermissionHandler\Enums\PermissionStatus;
use Nativephp\AllPermissionHandler\Enums\ServiceStatus;

class AllPermissionHandler
{
    /** @var callable|null */
    protected $onDenied;

    /** @var callable|null */
    protected $onGranted;

    /** @var callable|null */
    protected $onPermanentlyDenied;

    /** @var callable|null */
    protected $onRestricted;

    /** @var callable|null */
    protected $onLimited;

    /** @var callable|null */
    protected $onProvisional;

    /**
     * Alias for check().
     */
    public function status(Permission|string $permission): PermissionStatus
    {
        return $this->check($permission);
    }

    /**
     * Check a permission status.
     */
    public function check(Permission|string $permission): PermissionStatus
    {
        $response = $this->callNative(
            'AllPermissionHandler.Check',
            ['permission' => $this->normalizePermission($permission)]
        );

        return $this->permissionStatusFromResponse($response, PermissionStatus::Denied);
    }

    /**
     * Request a permission status.
     */
    public function request(Permission|string $permission): PermissionStatus
    {
        $response = $this->callNative(
            'AllPermissionHandler.Request',
            ['permission' => $this->normalizePermission($permission)]
        );

        $status = $this->permissionStatusFromResponse($response, PermissionStatus::Denied);
        $this->dispatchStatusCallback($status);

        return $status;
    }

    /**
     * Request multiple permissions.
     *
     * @param  array<int, Permission|string>  $permissions
     * @return array<string, PermissionStatus>
     */
    public function requestMultiple(array $permissions): array
    {
        $normalizedPermissions = array_map(
            fn (Permission|string $permission): string => $this->normalizePermission($permission),
            $permissions
        );

        $response = $this->callNative(
            'AllPermissionHandler.RequestMultiple',
            ['permissions' => array_values($normalizedPermissions)]
        );

        if (! is_array($response) || ! isset($response['statuses']) || ! is_array($response['statuses'])) {
            return [];
        }

        $statuses = [];
        foreach ($response['statuses'] as $permission => $status) {
            if (is_string($permission)) {
                $statuses[$permission] = PermissionStatus::tryFrom((int) $status) ?? PermissionStatus::Denied;
            }
        }

        return $statuses;
    }

    /**
     * Get service status for service-backed permissions.
     */
    public function serviceStatus(Permission|string $permission): ServiceStatus
    {
        $response = $this->callNative(
            'AllPermissionHandler.ServiceStatus',
            ['permission' => $this->normalizePermission($permission)]
        );

        if (! is_array($response)) {
            return ServiceStatus::NotApplicable;
        }

        return ServiceStatus::tryFrom((int) ($response['serviceStatus'] ?? ServiceStatus::NotApplicable->value))
            ?? ServiceStatus::NotApplicable;
    }

    /**
     * Open platform application settings.
     */
    public function openAppSettings(): bool
    {
        $response = $this->callNative('AllPermissionHandler.OpenAppSettings');

        if (! is_array($response)) {
            return false;
        }

        return (bool) ($response['opened'] ?? false);
    }

    public function shouldShowRequestRationale(Permission|string $permission): bool
    {
        $response = $this->callNative(
            'AllPermissionHandler.ShouldShowRequestRationale',
            ['permission' => $this->normalizePermission($permission)]
        );

        if (! is_array($response)) {
            return false;
        }

        return (bool) ($response['shouldShowRequestRationale'] ?? false);
    }

    public function onDeniedCallback(?callable $callback): static
    {
        $this->onDenied = $callback;

        return $this;
    }

    public function onGrantedCallback(?callable $callback): static
    {
        $this->onGranted = $callback;

        return $this;
    }

    public function onPermanentlyDeniedCallback(?callable $callback): static
    {
        $this->onPermanentlyDenied = $callback;

        return $this;
    }

    public function onRestrictedCallback(?callable $callback): static
    {
        $this->onRestricted = $callback;

        return $this;
    }

    public function onLimitedCallback(?callable $callback): static
    {
        $this->onLimited = $callback;

        return $this;
    }

    public function onProvisionalCallback(?callable $callback): static
    {
        $this->onProvisional = $callback;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function callNative(string $method, array $payload = []): ?array
    {
        if (! function_exists('nativephp_call')) {
            return null;
        }

        $result = nativephp_call($method, json_encode($payload));
        if (! $result) {
            return null;
        }

        $decoded = json_decode($result, true);
        if (! is_array($decoded)) {
            return null;
        }

        $data = $decoded['data'] ?? null;

        return is_array($data) ? $data : null;
    }

    protected function normalizePermission(Permission|string $permission): string
    {
        if ($permission instanceof Permission) {
            return $permission->value;
        }

        return $permission;
    }

    protected function permissionStatusFromResponse(?array $response, PermissionStatus $default): PermissionStatus
    {
        if (! is_array($response)) {
            return $default;
        }

        return PermissionStatus::tryFrom((int) ($response['status'] ?? $default->value)) ?? $default;
    }

    protected function dispatchStatusCallback(PermissionStatus $status): void
    {
        match ($status) {
            PermissionStatus::Denied => ($this->onDenied) && ($this->onDenied)(),
            PermissionStatus::Granted => ($this->onGranted) && ($this->onGranted)(),
            PermissionStatus::PermanentlyDenied => ($this->onPermanentlyDenied) && ($this->onPermanentlyDenied)(),
            PermissionStatus::Restricted => ($this->onRestricted) && ($this->onRestricted)(),
            PermissionStatus::Limited => ($this->onLimited) && ($this->onLimited)(),
            PermissionStatus::Provisional => ($this->onProvisional) && ($this->onProvisional)(),
        };
    }
}
