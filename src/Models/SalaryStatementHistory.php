<?php
namespace Common\Models;

class SalaryStatementHistory {
    public $id;
    public $companyDetails;
    public $clockInOutId;
    public $employeeId;
    public $employeeName;
    public $departmentId;
    public $departmentName;
    public $basicSalary;
    public $totalEarnSalary;
    public $otAmount;
    public $pfAmount;
    public $pfPercentage;
    public $totalPfAmount;
    public $ptAmount;
    public $totalEarnings;
    public $totalDeductions;
    public $totalPenaltyAmount;
    public $otherDeductions;
    public $netSalary;
    public $monthYear;
    public $month;
    public $year;
    public $totalPaidDays;
    public $totalWorkingDays;
    public $totalWorkingHours;
    public $totalDays;
    public $note;
    public $companyEmployee;
    public $generatedDate;

    public static $tableName = 'salary_statement_history';
    public static $fieldsMap = [
        'id' => 'id',
        'companyDetails' => 'company_id',
        'clockInOutId' => 'clock_in_out_id',
        'employeeId' => 'employee_id',
        'employeeName' => 'employee_name',
        'departmentId' => 'department_id',
        'departmentName' => 'department_name',
        'basicSalary' => 'basic_salary',
        'totalEarnSalary' => 'total_earn_salary',
        'otAmount' => 'ot_amount',
        'pfAmount' => 'pf_amount',
        'pfPercentage' => 'pf_percentage',
        'totalPfAmount' => 'total_pf_amount',
        'ptAmount' => 'pt_amount',
        'totalEarnings' => 'total_earnings',
        'totalDeductions' => 'total_deductions',
        'totalPenaltyAmount' => 'total_penalty_amount',
        'otherDeductions' => 'other_deductions',
        'netSalary' => 'net_salary',
        'monthYear' => 'salary_month_and_year',
        'month' => 'salary_month',
        'year' => 'salary_year',
        'totalPaidDays' => 'total_paid_days',
        'totalWorkingDays' => 'working_days',
        'totalWorkingHours' => 'working_hours',
        'totalDays' => 'total_days',
        'note' => 'note',
        'companyEmployee' => 'generated_by',
        'generatedDate' => 'generated_date',
    ];
}
