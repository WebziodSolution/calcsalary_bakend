<?php
namespace Common\Serializers;

class SalaryStatementHistorySerializer {
    public $id;
    public $clockInOutId;
    public $companyId;
    public $employeeId;
    public $employeeName;
    public $departmentId;
    public $departmentName;
    public $basicSalary;
    public $totalEarnSalary;
    public $otAmount;
    public $pfAmount;
    public $totalPfAmount;
    public $pfPercentage;
    public $ptAmount;
    public $totalEarnings;
    public $totalPenaltyAmount;
    public $otherDeductions;
    public $totalDeductions;
    public $netSalary;
    public $year;
    public $monthNumber;
    public $monthYear;
    public $totalPaidDays;
    public $totalWorkingDays;
    public $totalWorkingHours;
    public $totalDays;
    public $startDate;
    public $endDate;
    public $timeZone;
    public $note;
    public $generatedBy;
    public $deductionsList;
    public $allowanceList;
    public $used_leave;
}
