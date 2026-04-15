/**
 * AllPermissionHandler Plugin for NativePHP Mobile.
 */

const baseUrl = '/_native/api/call';

/**
 * Internal bridge call function
 * @private
 */
async function bridgeCall(method, params = {}) {
    const response = await fetch(baseUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ method, params })
    });

    const result = await response.json();

    if (result.status === 'error') {
        throw new Error(result.message || 'Native call failed');
    }

    const nativeResponse = result.data;
    if (nativeResponse && nativeResponse.data !== undefined) {
        return nativeResponse.data;
    }

    return nativeResponse;
}

/**
 * Check one permission status.
 * @param {string} permission
 * @returns {Promise<number>}
 */
export async function check(permission) {
    const response = await bridgeCall('AllPermissionHandler.Check', { permission });
    return normalizePermissionStatus(response?.status ?? response);
}

/**
 * Request one permission.
 * @param {string} permission
 * @returns {Promise<number>}
 */
export async function request(permission) {
    const response = await bridgeCall('AllPermissionHandler.Request', { permission });
    return normalizePermissionStatus(response?.status ?? response);
}

/**
 * Request multiple permissions.
 * @param {string[]} permissions
 * @returns {Promise<Record<string, number>>}
 */
export async function requestMultiple(permissions = []) {
    const response = await bridgeCall('AllPermissionHandler.RequestMultiple', { permissions });
    return response?.statuses ?? response ?? {};
}

/**
 * Check service status for a permission.
 * @param {string} permission
 * @returns {Promise<number>}
 */
export async function serviceStatus(permission) {
    const response = await bridgeCall('AllPermissionHandler.ServiceStatus', { permission });
    return normalizeServiceStatus(response?.serviceStatus ?? response);
}

/**
 * Open app settings page.
 * @returns {Promise<boolean>}
 */
export async function openAppSettings() {
    const response = await bridgeCall('AllPermissionHandler.OpenAppSettings');
    return Boolean(response?.opened ?? response);
}

/**
 * Ask native runtime if rationale should be shown before requesting.
 * @param {string} permission
 * @returns {Promise<boolean>}
 */
export async function shouldShowRequestRationale(permission) {
    const response = await bridgeCall('AllPermissionHandler.ShouldShowRequestRationale', { permission });
    return Boolean(response?.shouldShowRequestRationale ?? response);
}

/**
 * Alias for check().
 * @param {string} permission
 * @returns {Promise<number>}
 */
export async function status(permission) {
    return check(permission);
}

/**
 * Normalize permission status to expected integer range.
 * @param {unknown} status
 * @returns {number}
 */
export function normalizePermissionStatus(status) {
    const value = Number(status);
    return Number.isFinite(value) && value >= 0 && value <= 5 ? value : 0;
}

/**
 * Normalize service status to expected integer range.
 * @param {unknown} status
 * @returns {number}
 */
export function normalizeServiceStatus(status) {
    const value = Number(status);
    return Number.isFinite(value) && value >= 0 && value <= 2 ? value : 2;
}

/**
 * Request builder with callback-style hooks.
 */
class PermissionRequestBuilder {
    constructor(permission) {
        this.permission = permission;
        this.callbacks = {
            denied: null,
            granted: null,
            permanentlyDenied: null,
            restricted: null,
            limited: null,
            provisional: null
        };
    }

    onDeniedCallback(callback) {
        this.callbacks.denied = callback;
        return this;
    }

    onGrantedCallback(callback) {
        this.callbacks.granted = callback;
        return this;
    }

    onPermanentlyDeniedCallback(callback) {
        this.callbacks.permanentlyDenied = callback;
        return this;
    }

    onRestrictedCallback(callback) {
        this.callbacks.restricted = callback;
        return this;
    }

    onLimitedCallback(callback) {
        this.callbacks.limited = callback;
        return this;
    }

    onProvisionalCallback(callback) {
        this.callbacks.provisional = callback;
        return this;
    }

    async request() {
        const requestedStatus = await request(this.permission);
        this.dispatch(requestedStatus);
        return requestedStatus;
    }

    dispatch(status) {
        const normalized = normalizePermissionStatus(status);
        if (normalized === 0 && this.callbacks.denied) this.callbacks.denied();
        if (normalized === 1 && this.callbacks.granted) this.callbacks.granted();
        if (normalized === 2 && this.callbacks.restricted) this.callbacks.restricted();
        if (normalized === 3 && this.callbacks.limited) this.callbacks.limited();
        if (normalized === 4 && this.callbacks.permanentlyDenied) this.callbacks.permanentlyDenied();
        if (normalized === 5 && this.callbacks.provisional) this.callbacks.provisional();
    }
}

/**
 * Build a callback-based permission request flow.
 * @param {string} permission
 * @returns {PermissionRequestBuilder}
 */
export function withCallbacks(permission) {
    return new PermissionRequestBuilder(permission);
}

/**
 * AllPermissionHandler namespace object
 */
export const allPermissionHandler = {
    status,
    check,
    request,
    requestMultiple,
    serviceStatus,
    openAppSettings,
    shouldShowRequestRationale,
    withCallbacks
};

export default allPermissionHandler;