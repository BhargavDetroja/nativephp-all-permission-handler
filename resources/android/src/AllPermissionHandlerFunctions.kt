package com.nativephp.plugins.all_permission_handler

import android.Manifest
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Build
import android.os.PowerManager
import android.provider.Settings
import android.telephony.TelephonyManager
import android.bluetooth.BluetoothManager
import androidx.core.app.ActivityCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeError
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse

object AllPermissionHandlerFunctions {
    private const val STATUS_DENIED = 0
    private const val STATUS_GRANTED = 1
    private const val STATUS_RESTRICTED = 2
    private const val STATUS_LIMITED = 3
    private const val STATUS_PERMANENTLY_DENIED = 4
    private const val STATUS_PROVISIONAL = 5

    private const val SERVICE_DISABLED = 0
    private const val SERVICE_ENABLED = 1
    private const val SERVICE_NOT_APPLICABLE = 2

    private val permissionMap: Map<String, Array<String>> = mapOf(
        "camera" to arrayOf(Manifest.permission.CAMERA),
        "contacts" to arrayOf(Manifest.permission.READ_CONTACTS, Manifest.permission.WRITE_CONTACTS),
        "location" to arrayOf(Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION),
        "locationWhenInUse" to arrayOf(Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION),
        "locationAlways" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            arrayOf(Manifest.permission.ACCESS_BACKGROUND_LOCATION)
        } else {
            arrayOf(Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION)
        },
        "microphone" to arrayOf(Manifest.permission.RECORD_AUDIO),
        "phone" to arrayOf(Manifest.permission.READ_PHONE_STATE),
        "photos" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            arrayOf(Manifest.permission.READ_MEDIA_IMAGES)
        } else {
            arrayOf(Manifest.permission.READ_EXTERNAL_STORAGE)
        },
        "videos" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            arrayOf(Manifest.permission.READ_MEDIA_VIDEO)
        } else {
            arrayOf(Manifest.permission.READ_EXTERNAL_STORAGE)
        },
        "audio" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            arrayOf(Manifest.permission.READ_MEDIA_AUDIO)
        } else {
            arrayOf(Manifest.permission.READ_EXTERNAL_STORAGE)
        },
        "mediaLibrary" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            arrayOf(
                Manifest.permission.READ_MEDIA_IMAGES,
                Manifest.permission.READ_MEDIA_VIDEO,
                Manifest.permission.READ_MEDIA_AUDIO,
            )
        } else {
            arrayOf(Manifest.permission.READ_EXTERNAL_STORAGE)
        },
        "access_media_location" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            arrayOf(Manifest.permission.ACCESS_MEDIA_LOCATION)
        } else {
            emptyArray()
        },
        "sms" to arrayOf(Manifest.permission.SEND_SMS, Manifest.permission.RECEIVE_SMS, Manifest.permission.READ_SMS),
        "speech" to arrayOf(Manifest.permission.RECORD_AUDIO),
        "storage" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            emptyArray()
        } else {
            arrayOf(Manifest.permission.READ_EXTERNAL_STORAGE, Manifest.permission.WRITE_EXTERNAL_STORAGE)
        },
        "notification" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            arrayOf(Manifest.permission.POST_NOTIFICATIONS)
        } else {
            emptyArray()
        },
        "activity_recognition" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            arrayOf(Manifest.permission.ACTIVITY_RECOGNITION)
        } else {
            emptyArray()
        },
        "bluetooth" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            arrayOf(Manifest.permission.BLUETOOTH_CONNECT)
        } else {
            emptyArray()
        },
        "bluetoothScan" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) arrayOf(Manifest.permission.BLUETOOTH_SCAN) else emptyArray(),
        "bluetoothConnect" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) arrayOf(Manifest.permission.BLUETOOTH_CONNECT) else emptyArray(),
        "bluetoothAdvertise" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) arrayOf(Manifest.permission.BLUETOOTH_ADVERTISE) else emptyArray(),
        "nearbyWifiDevices" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) arrayOf(Manifest.permission.NEARBY_WIFI_DEVICES) else emptyArray(),
        "sensors" to arrayOf(Manifest.permission.BODY_SENSORS),
        "sensorsAlways" to if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) arrayOf(Manifest.permission.BODY_SENSORS_BACKGROUND) else emptyArray(),
        "calendar" to arrayOf(Manifest.permission.READ_CALENDAR, Manifest.permission.WRITE_CALENDAR),
        "calendarWriteOnly" to arrayOf(Manifest.permission.WRITE_CALENDAR),
        "calendarFullAccess" to arrayOf(Manifest.permission.READ_CALENDAR, Manifest.permission.WRITE_CALENDAR)
    )

    class Check(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val permission = parameters["permission"] as? String
                ?: throw BridgeError.InvalidParameters("'permission' is required")

            val status = getPermissionStatus(context, permission)
            return BridgeResponse.success(mapOf("status" to status))
        }
    }

    class Request(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val permission = parameters["permission"] as? String
                ?: throw BridgeError.InvalidParameters("'permission' is required")

            val status = requestPermission(activity, permission)
            return BridgeResponse.success(mapOf("status" to status))
        }
    }

    class RequestMultiple(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val permissions = (parameters["permissions"] as? List<*>)?.mapNotNull { it as? String } ?: emptyList()

            val statuses = mutableMapOf<String, Int>()
            permissions.forEach { permission ->
                statuses[permission] = requestPermission(activity, permission)
            }

            return BridgeResponse.success(mapOf("statuses" to statuses))
        }
    }

    class ServiceStatus(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val permission = parameters["permission"] as? String
                ?: throw BridgeError.InvalidParameters("'permission' is required")

            val serviceStatus = when (permission) {
                "location", "locationAlways", "locationWhenInUse" -> {
                    val enabled = isLocationEnabled(context)
                    if (enabled) SERVICE_ENABLED else SERVICE_DISABLED
                }
                "notification" -> {
                    if (NotificationManagerCompat.from(context).areNotificationsEnabled()) {
                        SERVICE_ENABLED
                    } else {
                        SERVICE_DISABLED
                    }
                }
                "phone" -> phoneServiceStatus(context)
                "bluetooth", "bluetoothScan", "bluetoothConnect", "bluetoothAdvertise" -> bluetoothServiceStatus(context)
                else -> SERVICE_NOT_APPLICABLE
            }

            return BridgeResponse.success(mapOf("serviceStatus" to serviceStatus))
        }
    }

    class OpenAppSettings(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val intent = Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS).apply {
                data = Uri.parse("package:${context.packageName}")
                addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
            }

            context.startActivity(intent)

            return BridgeResponse.success(mapOf("opened" to true))
        }
    }

    class ShouldShowRequestRationale(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val permission = parameters["permission"] as? String
                ?: throw BridgeError.InvalidParameters("'permission' is required")

            val mapped = mapToAndroidPermissions(permission)
            if (mapped.isEmpty()) {
                return BridgeResponse.success(mapOf("shouldShowRequestRationale" to false))
            }

            val shouldShow = mapped.any {
                ActivityCompat.shouldShowRequestPermissionRationale(activity, it)
            }

            return BridgeResponse.success(mapOf("shouldShowRequestRationale" to shouldShow))
        }
    }

    private fun getPermissionStatus(context: Context, permission: String): Int {
        when (permission) {
            "manageExternalStorage" -> {
                if (Build.VERSION.SDK_INT < Build.VERSION_CODES.R) {
                    return STATUS_GRANTED
                }
                return if (EnvironmentCompat.isExternalStorageManager()) STATUS_GRANTED else STATUS_DENIED
            }
            "systemAlertWindow" -> {
                return if (Settings.canDrawOverlays(context)) STATUS_GRANTED else STATUS_DENIED
            }
            "ignoreBatteryOptimizations" -> {
                val powerManager = context.getSystemService(Context.POWER_SERVICE) as? PowerManager
                val ignoring = powerManager?.isIgnoringBatteryOptimizations(context.packageName) ?: false
                return if (ignoring) STATUS_GRANTED else STATUS_DENIED
            }
            "requestInstallPackages" -> {
                if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) {
                    return STATUS_GRANTED
                }
                return if (context.packageManager.canRequestPackageInstalls()) STATUS_GRANTED else STATUS_DENIED
            }
            "accessNotificationPolicy" -> {
                val manager = context.getSystemService(Context.NOTIFICATION_SERVICE) as? android.app.NotificationManager
                val granted = manager?.isNotificationPolicyAccessGranted ?: false
                return if (granted) STATUS_GRANTED else STATUS_DENIED
            }
            "scheduleExactAlarm" -> {
                if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S) {
                    return STATUS_GRANTED
                }
                val alarmManager = context.getSystemService(Context.ALARM_SERVICE) as? android.app.AlarmManager
                val granted = alarmManager?.canScheduleExactAlarms() ?: false
                return if (granted) STATUS_GRANTED else STATUS_DENIED
            }
            "notification" -> {
                if (Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU) {
                    return if (NotificationManagerCompat.from(context).areNotificationsEnabled()) STATUS_GRANTED else STATUS_DENIED
                }
            }
        }

        val mapped = mapToAndroidPermissions(permission)
        if (mapped.isEmpty()) {
            return STATUS_DENIED
        }

        val allGranted = mapped.all { androidPermission ->
            ContextCompat.checkSelfPermission(context, androidPermission) == PackageManager.PERMISSION_GRANTED
        }

        return if (allGranted) STATUS_GRANTED else STATUS_DENIED
    }

    private fun requestPermission(activity: FragmentActivity, permission: String): Int {
        when (permission) {
            "manageExternalStorage" -> {
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
                    openSettings(activity, Settings.ACTION_MANAGE_APP_ALL_FILES_ACCESS_PERMISSION)
                }
                return getPermissionStatus(activity, permission)
            }
            "systemAlertWindow" -> {
                openSettings(activity, Settings.ACTION_MANAGE_OVERLAY_PERMISSION)
                return getPermissionStatus(activity, permission)
            }
            "ignoreBatteryOptimizations" -> {
                openSettings(activity, Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS)
                return getPermissionStatus(activity, permission)
            }
            "requestInstallPackages" -> {
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                    openSettings(activity, Settings.ACTION_MANAGE_UNKNOWN_APP_SOURCES)
                }
                return getPermissionStatus(activity, permission)
            }
            "accessNotificationPolicy" -> {
                openSettings(activity, Settings.ACTION_NOTIFICATION_POLICY_ACCESS_SETTINGS)
                return getPermissionStatus(activity, permission)
            }
            "scheduleExactAlarm" -> {
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                    openSettings(activity, Settings.ACTION_REQUEST_SCHEDULE_EXACT_ALARM)
                }
                return getPermissionStatus(activity, permission)
            }
        }

        val mapped = mapToAndroidPermissions(permission)
        if (mapped.isEmpty()) {
            return getPermissionStatus(activity, permission)
        }

        val pendingPermissions = mapped.filter {
            ContextCompat.checkSelfPermission(activity, it) != PackageManager.PERMISSION_GRANTED
        }

        if (pendingPermissions.isEmpty()) {
            return STATUS_GRANTED
        }

        activity.runOnUiThread {
            ActivityCompat.requestPermissions(
                activity,
                pendingPermissions.toTypedArray(),
                9001
            )
        }

        // Bridge calls are synchronous. Poll briefly to detect user choice after the system prompt.
        var requestedStatus = getPermissionStatus(activity, permission)
        val startedAt = System.currentTimeMillis()
        while (System.currentTimeMillis() - startedAt < 20000 && requestedStatus == STATUS_DENIED) {
            Thread.sleep(250)
            requestedStatus = getPermissionStatus(activity, permission)
        }

        if (requestedStatus == STATUS_DENIED) {
            val permanentlyDenied = pendingPermissions.any {
                ActivityCompat.checkSelfPermission(activity, it) != PackageManager.PERMISSION_GRANTED &&
                    !ActivityCompat.shouldShowRequestPermissionRationale(activity, it)
            }
            if (permanentlyDenied) {
                return STATUS_PERMANENTLY_DENIED
            }
        }

        return requestedStatus
    }

    private fun mapToAndroidPermissions(permission: String): Array<String> {
        return permissionMap[permission] ?: emptyArray()
    }

    private fun phoneServiceStatus(context: Context): Int {
        val packageManager = context.packageManager
        if (!packageManager.hasSystemFeature(PackageManager.FEATURE_TELEPHONY)) {
            return SERVICE_NOT_APPLICABLE
        }

        val telephonyManager = context.getSystemService(Context.TELEPHONY_SERVICE) as? TelephonyManager
            ?: return SERVICE_NOT_APPLICABLE

        return if (telephonyManager.simState == TelephonyManager.SIM_STATE_READY) {
            SERVICE_ENABLED
        } else {
            SERVICE_DISABLED
        }
    }

    private fun bluetoothServiceStatus(context: Context): Int {
        val packageManager = context.packageManager
        if (!packageManager.hasSystemFeature(PackageManager.FEATURE_BLUETOOTH)) {
            return SERVICE_NOT_APPLICABLE
        }

        val bluetoothManager = context.getSystemService(Context.BLUETOOTH_SERVICE) as? BluetoothManager
            ?: return SERVICE_NOT_APPLICABLE

        val adapter = bluetoothManager.adapter ?: return SERVICE_NOT_APPLICABLE
        return if (adapter.isEnabled) SERVICE_ENABLED else SERVICE_DISABLED
    }

    private fun openSettings(context: Context, action: String) {
        val intent = Intent(action).apply {
            data = Uri.parse("package:${context.packageName}")
            addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        }
        context.startActivity(intent)
    }

    private fun isLocationEnabled(context: Context): Boolean {
        return try {
            val locationMode = Settings.Secure.getInt(context.contentResolver, Settings.Secure.LOCATION_MODE)
            locationMode != Settings.Secure.LOCATION_MODE_OFF
        } catch (_: Throwable) {
            false
        }
    }
}

private object EnvironmentCompat {
    fun isExternalStorageManager(): Boolean {
        return Build.VERSION.SDK_INT < Build.VERSION_CODES.R || SettingsHelper.isExternalStorageManager()
    }
}

private object SettingsHelper {
    fun isExternalStorageManager(): Boolean {
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            android.os.Environment.isExternalStorageManager()
        } else {
            true
        }
    }
}
