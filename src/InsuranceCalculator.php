<?php
class InsuranceCalculator
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function getActivePolicy(string $date): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM insurance_policies WHERE is_active=1 AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?) ORDER BY effective_from DESC LIMIT 1");
        $stmt->bind_param('ss', $date, $date);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function calculate(float $baseSalary, array $policy, array $flags = []): array
    {
        $base = $baseSalary;
        if ((float)$policy['min_base_salary'] > 0) {
            $base = max($base, (float)$policy['min_base_salary']);
        }
        if ((float)$policy['max_base_salary'] > 0) {
            $base = min($base, (float)$policy['max_base_salary']);
        }

        $useSocial = $flags['social'] ?? true;
        $useHealth = $flags['health'] ?? true;
        $useUnemployment = $flags['unemployment'] ?? true;

        $employeeSocial = $useSocial ? $base * (float)$policy['employee_social_rate'] : 0;
        $employeeHealth = $useHealth ? $base * (float)$policy['employee_health_rate'] : 0;
        $employeeUnemployment = $useUnemployment ? $base * (float)$policy['employee_unemployment_rate'] : 0;
        $employerSocial = $useSocial ? $base * (float)$policy['employer_social_rate'] : 0;
        $employerHealth = $useHealth ? $base * (float)$policy['employer_health_rate'] : 0;
        $employerUnemployment = $useUnemployment ? $base * (float)$policy['employer_unemployment_rate'] : 0;

        return [
            'base' => round($base),
            'employee_social' => round($employeeSocial),
            'employee_health' => round($employeeHealth),
            'employee_unemployment' => round($employeeUnemployment),
            'employee_total' => round($employeeSocial + $employeeHealth + $employeeUnemployment),
            'employer_social' => round($employerSocial),
            'employer_health' => round($employerHealth),
            'employer_unemployment' => round($employerUnemployment),
            'employer_total' => round($employerSocial + $employerHealth + $employerUnemployment),
        ];
    }
}
