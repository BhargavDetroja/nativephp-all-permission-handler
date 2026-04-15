import Foundation
import AppTrackingTransparency
import AVFoundation
import CoreBluetooth
import Contacts
import CoreLocation
import CoreMotion
import EventKit
import Photos
import Speech
import UIKit
import UserNotifications

enum AllPermissionHandlerFunctions {
    private static let statusDenied = 0
    private static let statusGranted = 1
    private static let statusRestricted = 2
    private static let statusLimited = 3
    private static let statusPermanentlyDenied = 4
    private static let statusProvisional = 5

    private static let serviceDisabled = 0
    private static let serviceEnabled = 1
    private static let serviceNotApplicable = 2
    private static var locationRequester: LocationPermissionRequester?
    private static var bluetoothRequester: BluetoothPermissionRequester?

    class Check: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let permission = parameters["permission"] as? String else {
                throw BridgeError.invalidParameters("'permission' is required")
            }

            return BridgeResponse.success(data: ["status": currentStatus(for: permission)])
        }
    }

    class Request: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let permission = parameters["permission"] as? String else {
                throw BridgeError.invalidParameters("'permission' is required")
            }

            return BridgeResponse.success(data: ["status": requestStatus(for: permission)])
        }
    }

    class RequestMultiple: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let permissions = (parameters["permissions"] as? [Any])?.compactMap { $0 as? String } ?? []
            var statuses: [String: Int] = [:]
            permissions.forEach { permission in
                statuses[permission] = requestStatus(for: permission)
            }

            return BridgeResponse.success(data: ["statuses": statuses])
        }
    }

    class ServiceStatus: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let permission = parameters["permission"] as? String else {
                throw BridgeError.invalidParameters("'permission' is required")
            }

            let status: Int
            switch permission {
            case "location", "locationAlways", "locationWhenInUse":
                status = CLLocationManager.locationServicesEnabled() ? serviceEnabled : serviceDisabled
            case "notification":
                status = notificationStatus() == statusDenied ? serviceDisabled : serviceEnabled
            case "phone":
                status = phoneServiceStatus()
            case "bluetooth", "bluetoothScan", "bluetoothConnect", "bluetoothAdvertise":
                status = bluetoothServiceStatus()
            default:
                status = serviceNotApplicable
            }

            return BridgeResponse.success(data: ["serviceStatus": status])
        }
    }

    class OpenAppSettings: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let url = URL(string: UIApplication.openSettingsURLString) else {
                return BridgeResponse.success(data: ["opened": false])
            }

            DispatchQueue.main.async {
                UIApplication.shared.open(url)
            }

            return BridgeResponse.success(data: ["opened": true])
        }
    }

    class ShouldShowRequestRationale: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            return BridgeResponse.success(data: ["shouldShowRequestRationale": false])
        }
    }

    private static func currentStatus(for permission: String) -> Int {
        switch permission {
        case "camera":
            return mapAVStatus(AVCaptureDevice.authorizationStatus(for: .video))
        case "microphone":
            return mapAVStatus(AVCaptureDevice.authorizationStatus(for: .audio))
        case "location", "locationWhenInUse", "locationAlways":
            return mapLocationStatus(CLLocationManager.authorizationStatus())
        case "notification":
            return notificationStatus()
        case "photos":
            return mapPhotoStatus(PHPhotoLibrary.authorizationStatus(for: .readWrite))
        case "mediaLibrary":
            return mapPhotoStatus(PHPhotoLibrary.authorizationStatus(for: .readWrite))
        case "photosAddOnly":
            return mapPhotoStatus(PHPhotoLibrary.authorizationStatus(for: .addOnly))
        case "contacts":
            return mapContactStatus(CNContactStore.authorizationStatus(for: .contacts))
        case "calendar", "calendarWriteOnly", "calendarFullAccess":
            return mapEventStatus(EKEventStore.authorizationStatus(for: .event))
        case "reminders":
            return mapEventStatus(EKEventStore.authorizationStatus(for: .reminder))
        case "speech":
            if #available(iOS 10.0, *) {
                return mapSpeechStatus(SFSpeechRecognizer.authorizationStatus())
            }
            return statusDenied
        case "sensors", "sensorsAlways":
            return mapMotionStatus(CMMotionActivityManager.authorizationStatus())
        case "appTrackingTransparency":
            return trackingStatus()
        case "backgroundRefresh":
            return backgroundRefreshPermissionStatus()
        case "bluetooth", "bluetoothScan", "bluetoothConnect", "bluetoothAdvertise":
            return bluetoothPermissionStatus()
        case "criticalAlerts":
            return criticalAlertPermissionStatus()
        default:
            return statusDenied
        }
    }

    private static func requestStatus(for permission: String) -> Int {
        switch permission {
        case "camera":
            return waitForBooleanRequest { completion in
                AVCaptureDevice.requestAccess(for: .video, completionHandler: completion)
            }
        case "microphone":
            return waitForBooleanRequest { completion in
                AVCaptureDevice.requestAccess(for: .audio, completionHandler: completion)
            }
        case "photos":
            return waitForPhotoRequest(level: .readWrite)
        case "photosAddOnly":
            return waitForPhotoRequest(level: .addOnly)
        case "contacts":
            return waitForContactsRequest()
        case "calendar", "calendarWriteOnly", "calendarFullAccess":
            return waitForCalendarRequest(for: .event)
        case "reminders":
            return waitForCalendarRequest(for: .reminder)
        case "speech":
            return waitForSpeechRequest()
        case "location", "locationWhenInUse":
            return waitForLocationRequest(always: false)
        case "locationAlways":
            return waitForLocationRequest(always: true)
        case "notification":
            return waitForNotificationRequest()
        case "appTrackingTransparency":
            return waitForTrackingRequest()
        case "bluetooth", "bluetoothScan", "bluetoothConnect", "bluetoothAdvertise":
            return waitForBluetoothRequest()
        case "criticalAlerts":
            return waitForCriticalAlertRequest()
        default:
            return currentStatus(for: permission)
        }
    }

    private static func mapAVStatus(_ status: AVAuthorizationStatus) -> Int {
        switch status {
        case .authorized:
            return statusGranted
        case .restricted:
            return statusRestricted
        case .denied:
            return statusPermanentlyDenied
        case .notDetermined:
            return statusDenied
        @unknown default:
            return statusDenied
        }
    }

    private static func mapLocationStatus(_ status: CLAuthorizationStatus) -> Int {
        switch status {
        case .authorizedAlways, .authorizedWhenInUse:
            return statusGranted
        case .restricted:
            return statusRestricted
        case .denied:
            return statusPermanentlyDenied
        case .notDetermined:
            return statusDenied
        @unknown default:
            return statusDenied
        }
    }

    private static func mapPhotoStatus(_ status: PHAuthorizationStatus) -> Int {
        switch status {
        case .authorized:
            return statusGranted
        case .limited:
            return statusLimited
        case .restricted:
            return statusRestricted
        case .denied:
            return statusPermanentlyDenied
        case .notDetermined:
            return statusDenied
        @unknown default:
            return statusDenied
        }
    }

    private static func mapContactStatus(_ status: CNAuthorizationStatus) -> Int {
        switch status {
        case .authorized:
            return statusGranted
        case .restricted:
            return statusRestricted
        case .denied:
            return statusPermanentlyDenied
        case .notDetermined:
            return statusDenied
        @unknown default:
            return statusDenied
        }
    }

    private static func mapEventStatus(_ status: EKAuthorizationStatus) -> Int {
        switch status {
        case .fullAccess:
            return statusGranted
        case .writeOnly:
            return statusLimited
        case .restricted:
            return statusRestricted
        case .denied:
            return statusPermanentlyDenied
        case .notDetermined:
            return statusDenied
        @unknown default:
            return statusDenied
        }
    }

    private static func mapSpeechStatus(_ status: SFSpeechRecognizerAuthorizationStatus) -> Int {
        switch status {
        case .authorized:
            return statusGranted
        case .restricted:
            return statusRestricted
        case .denied:
            return statusPermanentlyDenied
        case .notDetermined:
            return statusDenied
        @unknown default:
            return statusDenied
        }
    }

    private static func mapMotionStatus(_ status: CMAuthorizationStatus) -> Int {
        switch status {
        case .authorized:
            return statusGranted
        case .restricted:
            return statusRestricted
        case .denied:
            return statusPermanentlyDenied
        case .notDetermined:
            return statusDenied
        @unknown default:
            return statusDenied
        }
    }

    private static func waitForBooleanRequest(_ request: (@escaping (Bool) -> Void) -> Void) -> Int {
        let semaphore = DispatchSemaphore(value: 0)
        var granted = false
        request { value in
            granted = value
            semaphore.signal()
        }
        _ = semaphore.wait(timeout: .now() + 20)
        return granted ? statusGranted : statusDenied
    }

    private static func waitForPhotoRequest(level: PHAccessLevel) -> Int {
        let semaphore = DispatchSemaphore(value: 0)
        var status = statusDenied
        PHPhotoLibrary.requestAuthorization(for: level) { value in
            status = mapPhotoStatus(value)
            semaphore.signal()
        }
        _ = semaphore.wait(timeout: .now() + 20)
        return status
    }

    private static func waitForContactsRequest() -> Int {
        let semaphore = DispatchSemaphore(value: 0)
        var status = statusDenied
        CNContactStore().requestAccess(for: .contacts) { _, _ in
            status = mapContactStatus(CNContactStore.authorizationStatus(for: .contacts))
            semaphore.signal()
        }
        _ = semaphore.wait(timeout: .now() + 20)
        return status
    }

    private static func waitForCalendarRequest(for entityType: EKEntityType) -> Int {
        let semaphore = DispatchSemaphore(value: 0)
        var status = statusDenied
        let store = EKEventStore()

        if #available(iOS 17.0, *) {
            switch entityType {
            case .event:
                store.requestFullAccessToEvents { _, _ in
                    status = mapEventStatus(EKEventStore.authorizationStatus(for: .event))
                    semaphore.signal()
                }
            case .reminder:
                store.requestFullAccessToReminders { _, _ in
                    status = mapEventStatus(EKEventStore.authorizationStatus(for: .reminder))
                    semaphore.signal()
                }
            @unknown default:
                semaphore.signal()
            }
        } else {
            store.requestAccess(to: entityType) { _, _ in
                status = mapEventStatus(EKEventStore.authorizationStatus(for: entityType))
                semaphore.signal()
            }
        }

        _ = semaphore.wait(timeout: .now() + 20)
        return status
    }

    private static func notificationStatus() -> Int {
        let semaphore = DispatchSemaphore(value: 0)
        var status = statusDenied
        UNUserNotificationCenter.current().getNotificationSettings { settings in
            switch settings.authorizationStatus {
            case .authorized:
                status = statusGranted
            case .provisional:
                status = statusProvisional
            case .ephemeral:
                status = statusLimited
            case .denied:
                status = statusPermanentlyDenied
            case .notDetermined:
                status = statusDenied
            @unknown default:
                status = statusDenied
            }
            semaphore.signal()
        }
        _ = semaphore.wait(timeout: .now() + 10)
        return status
    }

    private static func waitForNotificationRequest() -> Int {
        let semaphore = DispatchSemaphore(value: 0)
        var status = statusDenied
        UNUserNotificationCenter.current().requestAuthorization(options: [.alert, .badge, .sound]) { granted, _ in
            status = granted ? statusGranted : notificationStatus()
            semaphore.signal()
        }
        _ = semaphore.wait(timeout: .now() + 20)
        return status
    }

    private static func waitForSpeechRequest() -> Int {
        guard #available(iOS 10.0, *) else {
            return statusDenied
        }

        let semaphore = DispatchSemaphore(value: 0)
        var status = statusDenied
        SFSpeechRecognizer.requestAuthorization { value in
            status = mapSpeechStatus(value)
            semaphore.signal()
        }
        _ = semaphore.wait(timeout: .now() + 20)
        return status
    }

    private static func waitForLocationRequest(always: Bool) -> Int {
        let semaphore = DispatchSemaphore(value: 0)
        locationRequester = LocationPermissionRequester { _ in
            semaphore.signal()
            locationRequester = nil
        }

        DispatchQueue.main.async {
            if always {
                locationRequester?.manager.requestAlwaysAuthorization()
            } else {
                locationRequester?.manager.requestWhenInUseAuthorization()
            }
        }

        _ = semaphore.wait(timeout: .now() + 20)
        return currentStatus(for: always ? "locationAlways" : "locationWhenInUse")
    }

    private static func trackingStatus() -> Int {
        guard #available(iOS 14.0, *) else {
            return statusDenied
        }

        switch ATTrackingManager.trackingAuthorizationStatus {
        case .authorized:
            return statusGranted
        case .denied:
            return statusPermanentlyDenied
        case .restricted:
            return statusRestricted
        case .notDetermined:
            return statusDenied
        @unknown default:
            return statusDenied
        }
    }

    private static func waitForTrackingRequest() -> Int {
        guard #available(iOS 14.0, *) else {
            return statusDenied
        }

        let semaphore = DispatchSemaphore(value: 0)
        var status = statusDenied
        ATTrackingManager.requestTrackingAuthorization { value in
            switch value {
            case .authorized:
                status = statusGranted
            case .denied:
                status = statusPermanentlyDenied
            case .restricted:
                status = statusRestricted
            case .notDetermined:
                status = statusDenied
            @unknown default:
                status = statusDenied
            }
            semaphore.signal()
        }

        _ = semaphore.wait(timeout: .now() + 20)
        return status
    }

    private static func backgroundRefreshPermissionStatus() -> Int {
        switch UIApplication.shared.backgroundRefreshStatus {
        case .available:
            return statusGranted
        case .denied:
            return statusPermanentlyDenied
        case .restricted:
            return statusRestricted
        @unknown default:
            return statusDenied
        }
    }

    private static func bluetoothPermissionStatus() -> Int {
        if #available(iOS 13.1, *) {
            switch CBManager.authorization {
            case .allowedAlways:
                return statusGranted
            case .denied:
                return statusPermanentlyDenied
            case .restricted:
                return statusRestricted
            case .notDetermined:
                return statusDenied
            @unknown default:
                return statusDenied
            }
        }

        return statusGranted
    }

    private static func waitForBluetoothRequest() -> Int {
        if #available(iOS 13.1, *) {
            let semaphore = DispatchSemaphore(value: 0)
            bluetoothRequester = BluetoothPermissionRequester {
                semaphore.signal()
                bluetoothRequester = nil
            }
            _ = semaphore.wait(timeout: .now() + 20)
            return bluetoothPermissionStatus()
        }

        return statusGranted
    }

    private static func phoneServiceStatus() -> Int {
        guard let url = URL(string: "tel://") else {
            return serviceNotApplicable
        }

        return UIApplication.shared.canOpenURL(url) ? serviceEnabled : serviceNotApplicable
    }

    private static func bluetoothServiceStatus() -> Int {
        let permission = bluetoothPermissionStatus()
        if permission == statusDenied || permission == statusPermanentlyDenied || permission == statusRestricted {
            return serviceDisabled
        }
        return serviceEnabled
    }

    private static func criticalAlertPermissionStatus() -> Int {
        let semaphore = DispatchSemaphore(value: 0)
        var status = statusDenied
        UNUserNotificationCenter.current().getNotificationSettings { settings in
            if #available(iOS 12.0, *) {
                switch settings.criticalAlertSetting {
                case .enabled:
                    status = statusGranted
                case .disabled:
                    status = settings.authorizationStatus == .denied ? statusPermanentlyDenied : statusDenied
                case .notSupported:
                    status = statusDenied
                @unknown default:
                    status = statusDenied
                }
            } else {
                status = statusDenied
            }
            semaphore.signal()
        }
        _ = semaphore.wait(timeout: .now() + 10)
        return status
    }

    private static func waitForCriticalAlertRequest() -> Int {
        let semaphore = DispatchSemaphore(value: 0)
        var status = statusDenied
        var options: UNAuthorizationOptions = [.alert, .badge, .sound]
        if #available(iOS 12.0, *) {
            options.insert(.criticalAlert)
        }
        UNUserNotificationCenter.current().requestAuthorization(options: options) { _, _ in
            status = criticalAlertPermissionStatus()
            semaphore.signal()
        }
        _ = semaphore.wait(timeout: .now() + 20)
        return status
    }
}

private final class LocationPermissionRequester: NSObject, CLLocationManagerDelegate {
    let manager: CLLocationManager
    private let completion: (CLAuthorizationStatus) -> Void

    init(completion: @escaping (CLAuthorizationStatus) -> Void) {
        self.manager = CLLocationManager()
        self.completion = completion
        super.init()
        self.manager.delegate = self
    }

    func locationManagerDidChangeAuthorization(_ manager: CLLocationManager) {
        completion(manager.authorizationStatus)
    }
}

private final class BluetoothPermissionRequester: NSObject, CBPeripheralManagerDelegate {
    private var manager: CBPeripheralManager?
    private let completion: () -> Void

    init(completion: @escaping () -> Void) {
        self.completion = completion
        super.init()
        DispatchQueue.main.async {
            self.manager = CBPeripheralManager(delegate: self, queue: nil)
        }
    }

    func peripheralManagerDidUpdateState(_ peripheral: CBPeripheralManager) {
        completion()
    }
}
