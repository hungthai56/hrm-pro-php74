<?php
/**
 * Wrapper kết nối Ronald Jack/ZK.
 *
 * Bản scaffold không đóng gói thư viện bên thứ ba. Khi triển khai thật:
 * 1. Tải thư viện PHP ZKLib tương thích máy Ronald Jack vào libs/ronaldjack.
 * 2. Điều chỉnh method fetchAttendance() theo format thư viện.
 * 3. Kiểm tra firewall LAN, port thường gặp là 4370.
 */
class RonaldJackClient
{
    private string $ip;
    private int $port;

    public function __construct(string $ip, int $port = 4370)
    {
        $this->ip = $ip;
        $this->port = $port;
    }

    public function fetchAttendance(): array
    {
        $zklib = dirname(__DIR__) . '/libs/ronaldjack/zklib.php';
        if (!file_exists($zklib)) {
            throw new RuntimeException('Chưa có thư viện ZKLib trong libs/ronaldjack. Hãy thêm thư viện thật hoặc dùng import log thủ công.');
        }

        require_once $zklib;

        if (!class_exists('ZKLib')) {
            throw new RuntimeException('Không tìm thấy class ZKLib. Vui lòng kiểm tra thư viện Ronald Jack/ZK.');
        }

        $zk = new ZKLib($this->ip, $this->port);
        $zk->connect();
        $logs = $zk->getAttendance();
        $zk->disconnect();

        $normalized = [];
        foreach ($logs as $log) {
            $attendanceCode = $log['id'] ?? $log['uid'] ?? $log['user_id'] ?? null;
            $punchTime = $log['timestamp'] ?? $log['time'] ?? $log['datetime'] ?? null;
            if (!$attendanceCode || !$punchTime) {
                continue;
            }
            $normalized[] = [
                'attendance_code' => (string)$attendanceCode,
                'punch_time' => date('Y-m-d H:i:s', strtotime($punchTime)),
                'punch_type' => $log['type'] ?? null,
                'verify_type' => $log['state'] ?? null,
                'raw' => $log,
            ];
        }
        return $normalized;
    }
}
