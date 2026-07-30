<?php
namespace Common\Models;

class CompanyShift {
    public $id;
    public $companyDetails;
    public $shiftName;
    public $shiftType;
    public $startTime;
    public $endTime;
    public $hours;
    public $totalHours;

    public static $tableName = 'company_shift';
    public static $fieldsMap = [
        'id' => 'id',
        'companyDetails' => 'company_id',
        'shiftName' => 'shift_name',
        'shiftType' => 'shift_type',
        'startTime' => 'time_start',
        'endTime' => 'time_end',
        'hours' => 'hours',
        'totalHours' => 'total_hours',
    ];
}
