<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Device;

use DateTimeImmutable;
use MJ\LeaveModule\Reporting\AttendanceRecord;
use RuntimeException;

final class HttpF22DeviceClient implements F22DeviceClientInterface
{
    /** @var array<string, DeviceConnectionSettings> */
    private array $settingsBySerial;

    /** @param DeviceConnectionSettings[] $settings */
    public function __construct(private readonly HttpTransportInterface $transport, array $settings)
    {
        $this->settingsBySerial = [];
        foreach ($settings as $setting) {
            $this->settingsBySerial[$setting->serialNumber] = $setting;
        }
    }

    public function syncEmployees(string $deviceSerial, array $employeeNamesById): void
    {
        $setting = $this->settings($deviceSerial);
        $url = $this->baseUrl($setting) . '/api/employees/sync';

        $response = $this->transport->postJson($url, [
            'serial_number' => $deviceSerial,
            'sync_mode' => $setting->syncMode,
            'employees' => $employeeNamesById,
        ], $setting->heartbeatSeconds);

        if (($response['ok'] ?? false) !== true) {
            throw new RuntimeException('Employee sync failed on F22 device.');
        }
    }

    public function pullPunchLogs(string $deviceSerial): array
    {
        $setting = $this->settings($deviceSerial);
        $url = $this->baseUrl($setting) . '/api/punch-logs?serial=' . urlencode($deviceSerial);
        $response = $this->transport->getJson($url, $setting->heartbeatSeconds);

        $records = [];
        $items = $response['logs'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $records[] = new AttendanceRecord(
                (int) ($item['employee_id'] ?? 0),
                (string) ($item['team'] ?? 'UNKNOWN'),
                new DateTimeImmutable((string) ($item['punch_time'] ?? 'now')),
                (float) ($item['worked_hours'] ?? 0.0),
                (bool) ($item['present'] ?? true),
            );
        }

        return $records;
    }

    public function heartbeat(string $deviceSerial): bool
    {
        $setting = $this->settings($deviceSerial);
        $url = $this->baseUrl($setting) . '/api/heartbeat?serial=' . urlencode($deviceSerial);
        $response = $this->transport->getJson($url, $setting->heartbeatSeconds);
        return ($response['status'] ?? '') === 'ok';
    }

    public function triggerRemoteEnrollment(string $deviceSerial, int $employeeId, string $mode): bool
    {
        $setting = $this->settings($deviceSerial);
        $url = $this->baseUrl($setting) . '/api/enrollment/remote';
        $response = $this->transport->postJson($url, [
            'serial_number' => $deviceSerial,
            'employee_id' => $employeeId,
            'mode' => strtoupper($mode),
        ], $setting->heartbeatSeconds);

        return ($response['ok'] ?? false) === true;
    }

    private function settings(string $deviceSerial): DeviceConnectionSettings
    {
        if (!isset($this->settingsBySerial[$deviceSerial])) {
            throw new RuntimeException("No connection settings configured for device {$deviceSerial}.");
        }

        return $this->settingsBySerial[$deviceSerial];
    }

    private function baseUrl(DeviceConnectionSettings $settings): string
    {
        return 'http://' . $settings->ipAddress;
    }
}
