<?php
namespace Common\Models;

class EmployeeLeaveMaster {
    public $id;
    public $companyEmployee;
    public $leaveType;
    public $totalLeave;
    public $usedLeave;

    public static $tableName = 'employee_leave_master';
    public static $fieldsMap = [
        'id' => 'id',
        'companyEmployee' => 'employee_id',
        'leaveType' => 'leave_type_id',
        'totalLeave' => 'total_leave',
        'usedLeave' => 'used_leave',
    ];
}
