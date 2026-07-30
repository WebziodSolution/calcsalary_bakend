<?php
namespace Common\Serializers;

class SalaryStatementRequestSerializer {
    public $employeeIds;
    public $departmentIds;
    public $month;
    public $year;
    public $companyId;
    public $startDate;
    public $endDate;
    public $timeZone;
}
