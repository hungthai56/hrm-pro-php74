<?php
class AttendanceProcessor
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function processRange(string $from, string $to): int
    {
        $employees = $this->db->query("SELECT * FROM employees WHERE attendance_code IS NOT NULL AND attendance_code <> '' AND status IN ('working','probation')");
        $count = 0;
        while ($employee = $employees->fetch_assoc()) {
            $period = new DatePeriod(new DateTime($from), new DateInterval('P1D'), (new DateTime($to))->modify('+1 day'));
            foreach ($period as $date) {
                $workDate = $date->format('Y-m-d');
                if ($this->processEmployeeDate($employee, $workDate)) {
                    $count++;
                }
            }
        }
        return $count;
    }

    public function processEmployeeDate(array $employee, string $workDate): bool
    {
        $shift = $this->getEmployeeShift((int)$employee['id'], $workDate);
        if (!$shift) {
            return false;
        }

        $logs = $this->getLogs($employee['attendance_code'], $workDate, (int)$shift['is_overnight']);
        if (count($logs) === 0) {
            $this->upsertDaily($employee, $workDate, $shift, null, null, 0, 0, 0, 0, 0, 0, 'absent', 'Không có log chấm công');
            return true;
        }

        $checkIn = $logs[0];
        $checkOut = count($logs) > 1 ? $logs[count($logs) - 1] : null;
        if (!$checkOut || $checkOut === $checkIn) {
            $this->upsertDaily($employee, $workDate, $shift, $checkIn, null, 0, 0, 0, 0, 0, 0, 'missing_checkout', 'Thiếu giờ ra');
            return true;
        }

        $start = new DateTime($workDate . ' ' . $shift['start_time']);
        $end = new DateTime($workDate . ' ' . $shift['end_time']);
        if ((int)$shift['is_overnight'] === 1 || $end <= $start) {
            $end->modify('+1 day');
        }
        $in = new DateTime($checkIn);
        $out = new DateTime($checkOut);

        $actualMinutes = max(0, (int)(($out->getTimestamp() - $in->getTimestamp()) / 60));
        $workMinutes = max(0, $actualMinutes - (int)$shift['break_minutes']);
        $lateMinutes = max(0, (int)(($in->getTimestamp() - $start->getTimestamp()) / 60) - (int)$shift['allow_late_minutes']);
        $earlyMinutes = max(0, (int)(($end->getTimestamp() - $out->getTimestamp()) / 60) - (int)$shift['allow_early_minutes']);
        $standard = max(1, (int)$shift['standard_work_minutes']);
        $overtime = max(0, $workMinutes - $standard - (int)$shift['overtime_after_minutes']);
        $workDay = min(1, round($workMinutes / $standard, 2));
        $nightMinutes = $this->calculateNightMinutes($in, $out);

        $status = 'normal';
        if ($lateMinutes > 0 && $earlyMinutes > 0) { $status = 'late_early'; }
        elseif ($lateMinutes > 0) { $status = 'late'; }
        elseif ($earlyMinutes > 0) { $status = 'early_leave'; }

        $this->upsertDaily($employee, $workDate, $shift, $checkIn, $checkOut, $workMinutes, $lateMinutes, $earlyMinutes, $overtime, $nightMinutes, $workDay, $status, null);
        return true;
    }

    private function getEmployeeShift(int $employeeId, string $workDate): ?array
    {
        $stmt = $this->db->prepare("SELECT s.* FROM employee_shifts es JOIN work_shifts s ON s.id=es.shift_id WHERE es.employee_id=? AND es.work_date=? LIMIT 1");
        $stmt->bind_param('is', $employeeId, $workDate);
        $stmt->execute();
        $shift = $stmt->get_result()->fetch_assoc();
        if ($shift) { return $shift; }
        $res = $this->db->query("SELECT * FROM work_shifts WHERE is_active=1 ORDER BY id LIMIT 1");
        $row = $res ? $res->fetch_assoc() : null;
        return $row ?: null;
    }

    private function getLogs(string $attendanceCode, string $workDate, int $isOvernight): array
    {
        if ($isOvernight) {
            $start = $workDate . ' 00:00:00';
            $end = date('Y-m-d 12:00:00', strtotime($workDate . ' +1 day'));
            $stmt = $this->db->prepare("SELECT punch_time FROM attendance_raw_logs WHERE attendance_code=? AND punch_time BETWEEN ? AND ? ORDER BY punch_time ASC");
            $stmt->bind_param('sss', $attendanceCode, $start, $end);
        } else {
            $stmt = $this->db->prepare("SELECT punch_time FROM attendance_raw_logs WHERE attendance_code=? AND punch_date=? ORDER BY punch_time ASC");
            $stmt->bind_param('ss', $attendanceCode, $workDate);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $logs = [];
        while ($r = $res->fetch_assoc()) { $logs[] = $r['punch_time']; }
        return $logs;
    }

    private function upsertDaily(array $employee, string $workDate, array $shift, ?string $checkIn, ?string $checkOut, int $workMinutes, int $late, int $early, int $overtime, int $nightMinutes, float $workDay, string $status, ?string $note): void
    {
        $employeeId = (int)$employee['id'];
        $shiftId = (int)$shift['id'];
        $paidWorkDay = $workDay;
        $stmt = $this->db->prepare("INSERT INTO attendance_daily (employee_id, work_date, shift_id, check_in, check_out, work_minutes, late_minutes, early_leave_minutes, overtime_minutes, night_minutes, work_day, paid_work_day, status, note, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE shift_id=VALUES(shift_id), check_in=VALUES(check_in), check_out=VALUES(check_out), work_minutes=VALUES(work_minutes), late_minutes=VALUES(late_minutes), early_leave_minutes=VALUES(early_leave_minutes), overtime_minutes=VALUES(overtime_minutes), night_minutes=VALUES(night_minutes), work_day=VALUES(work_day), paid_work_day=VALUES(paid_work_day), status=VALUES(status), note=VALUES(note), updated_at=NOW()");
        $stmt->bind_param('isissiiiiiddss', $employeeId, $workDate, $shiftId, $checkIn, $checkOut, $workMinutes, $late, $early, $overtime, $nightMinutes, $workDay, $paidWorkDay, $status, $note);
        $stmt->execute();
    }

    private function calculateNightMinutes(DateTime $in, DateTime $out): int
    {
        $minutes = 0;
        $cursor = clone $in;
        while ($cursor < $out) {
            $hour = (int)$cursor->format('H');
            if ($hour >= 22 || $hour < 6) {
                $minutes++;
            }
            $cursor->modify('+1 minute');
            if ($minutes > 1440) { break; }
        }
        return $minutes;
    }
}
