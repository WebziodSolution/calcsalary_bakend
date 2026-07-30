<?php
namespace Common\Models;

class EmploymentInfo {
    public $id;
    public $workPhone;
    public $ext;
    public $workEmail;
    public $hireDate;
    public $status;
    public $paidPension;
    public $statutoryEmployee;
    public $exclusionIndicator;
    public $keyEmployeeIndicator;
    public $unionIndicator;
    public $hce;
    public $eligibilityIndicator;
    public $companyEmployee;

    public static $tableName = 'employment_info';
    public static $fieldsMap = [
        'id' => 'id',
        'workPhone' => 'work_phone',
        'ext' => 'ext',
        'workEmail' => 'work_email',
        'hireDate' => 'hire_date',
        'status' => 'status',
        'paidPension' => 'paid_pension',
        'statutoryEmployee' => 'statutory_employee',
        'exclusionIndicator' => 'exclusion_indicator',
        'keyEmployeeIndicator' => 'key_employee_indicator',
        'unionIndicator' => 'union_indicator',
        'hce' => 'hce',
        'eligibilityIndicator' => 'eligibility_indicator',
        'companyEmployee' => 'employee_id',
    ];
}
