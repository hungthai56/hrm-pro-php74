<?php
require_once __DIR__ . '/InsuranceCalculator.php';

class PayrollCalculator
{
    private mysqli $db;
    private InsuranceCalculator $insurance;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
        $this->insurance = new InsuranceCalculator($db);
    }

    public function generateForPeriod(int $periodId): int
    {
        $stmt = $this->db->prepare("SELECT * FROM payroll_periods WHERE id=? LIMIT 1");
        $stmt->bind_param('i', $periodId);
        $stmt->execute();
        $period = $stmt->get_result()->fetch_assoc();
        if (!$period) { return 0; }

        $policy = $this->insurance->getActivePolicy($period['to_date']);
        $employees = $this->db->query("SELECT * FROM employees WHERE status IN ('working','probation') ORDER BY employee_code");
        $count = 0;
        while ($emp = $employees->fetch_assoc()) {
            $this->generateEmployeePayroll($period, $emp, $policy);
            $count++;
        }
        $stmt = $this->db->prepare("UPDATE payroll_periods SET status='calculated' WHERE id=? AND status='draft'");
        $stmt->bind_param('i', $periodId);
        $stmt->execute();
        return $count;
    }

    private function generateEmployeePayroll(array $period, array $emp, ?array $policy): void
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(paid_work_day),0) AS days, COALESCE(SUM(overtime_minutes),0) AS overtime_minutes, COALESCE(SUM(night_minutes),0) AS night_minutes FROM attendance_daily WHERE employee_id=? AND work_date BETWEEN ? AND ?");
        $stmt->bind_param('iss', $emp['id'], $period['from_date'], $period['to_date']);
        $stmt->execute();
        $att = $stmt->get_result()->fetch_assoc();

        $standardDays = max(1, (float)$period['standard_work_days']);
        $actualDays = (float)$att['days'];
        $baseSalary = (float)$emp['base_salary'];
        $workingSalary = round($baseSalary / $standardDays * $actualDays);
        $overtimePay = round(($baseSalary / $standardDays / 8) * ((float)$att['overtime_minutes'] / 60) * 1.5);
        $nightPay = round(($baseSalary / $standardDays / 8) * ((float)$att['night_minutes'] / 60) * 0.3);

        $ins = ['employee_total' => 0, 'employer_total' => 0];
        if ($policy) {
            $ins = $this->insurance->calculate((float)$emp['insurance_base_salary'], $policy);
        }

        $gross = $workingSalary + $overtimePay + $nightPay;
        $deduction = $ins['employee_total'];
        $net = $gross - $deduction;

        $stmt = $this->db->prepare("INSERT INTO payrolls (payroll_period_id, employee_id, standard_work_days, actual_work_days, base_salary, working_salary, gross_salary, total_earning, total_deduction, employee_insurance_amount, employer_insurance_amount, net_salary, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE standard_work_days=VALUES(standard_work_days), actual_work_days=VALUES(actual_work_days), base_salary=VALUES(base_salary), working_salary=VALUES(working_salary), gross_salary=VALUES(gross_salary), total_earning=VALUES(total_earning), total_deduction=VALUES(total_deduction), employee_insurance_amount=VALUES(employee_insurance_amount), employer_insurance_amount=VALUES(employer_insurance_amount), net_salary=VALUES(net_salary), updated_at=NOW()");
        $stmt->bind_param('iidddddddddd', $period['id'], $emp['id'], $standardDays, $actualDays, $baseSalary, $workingSalary, $gross, $gross, $deduction, $ins['employee_total'], $ins['employer_total'], $net);
        $stmt->execute();

        $payrollId = $this->getPayrollId((int)$period['id'], (int)$emp['id']);
        if ($payrollId) {
            $this->db->query("DELETE FROM payroll_items WHERE payroll_id=" . (int)$payrollId);
            $this->addItem($payrollId, 'WORKING_SALARY', 'Lương theo công', 'earning', $workingSalary, 'base_salary / standard_work_days * actual_work_days');
            $this->addItem($payrollId, 'OVERTIME_PAY', 'Tiền tăng ca', 'earning', $overtimePay, 'hour_rate * overtime_hours * 1.5');
            $this->addItem($payrollId, 'NIGHT_SHIFT_PAY', 'Phụ cấp ca đêm', 'earning', $nightPay, 'hour_rate * night_hours * 0.3');
            $this->addItem($payrollId, 'INSURANCE_EMPLOYEE', 'Bảo hiểm người lao động đóng', 'deduction', $ins['employee_total'], 'BHXH + BHYT + BHTN');
            $this->addItem($payrollId, 'INSURANCE_COMPANY', 'Bảo hiểm công ty đóng', 'company_cost', $ins['employer_total'], 'Chi phí công ty');
        }
    }

    private function getPayrollId(int $periodId, int $employeeId): ?int
    {
        $stmt = $this->db->prepare("SELECT id FROM payrolls WHERE payroll_period_id=? AND employee_id=? LIMIT 1");
        $stmt->bind_param('ii', $periodId, $employeeId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (int)$row['id'] : null;
    }

    private function addItem(int $payrollId, string $code, string $name, string $type, float $amount, string $formula = ''): void
    {
        $stmt = $this->db->prepare("INSERT INTO payroll_items (payroll_id, component_code, component_name, component_type, amount, formula_snapshot) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssds', $payrollId, $code, $name, $type, $amount, $formula);
        $stmt->execute();
    }
}
