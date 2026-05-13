<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../src/RonaldJackClient.php';

$devices = db()->query("SELECT * FROM attendance_devices WHERE is_active=1");
$total = 0;
while ($device = $devices->fetch_assoc()) {
    try {
        $client = new RonaldJackClient($device['device_ip'], (int)$device['device_port']);
        $logs = $client->fetchAttendance();
        foreach ($logs as $log) {
            $punchDate = date('Y-m-d', strtotime($log['punch_time']));
            $raw = json_encode($log['raw'], JSON_UNESCAPED_UNICODE);
            $stmt = db()->prepare("INSERT IGNORE INTO attendance_raw_logs (device_id, attendance_code, punch_time, punch_date, punch_type, verify_type, raw_data) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('issssss', $device['id'], $log['attendance_code'], $log['punch_time'], $punchDate, $log['punch_type'], $log['verify_type'], $raw);
            $stmt->execute();
            $total += $stmt->affected_rows > 0 ? 1 : 0;
        }
        $stmt = db()->prepare("UPDATE attendance_devices SET last_sync_at=NOW() WHERE id=?");
        $stmt->bind_param('i', $device['id']);
        $stmt->execute();
    } catch (Throwable $e) {
        error_log('Sync RonaldJack error ' . $device['device_ip'] . ': ' . $e->getMessage());
    }
}

echo "Synced {$total} new attendance logs\n";
