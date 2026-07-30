<?php
namespace Common\Models;

class AttendancePenaltyRules {
    public $id;
    public $ruleName;
    public $companyDetails;
    public $companyEmployee;
    public $minutes;
    public $deductionType;
    public $amount;
    public $count;
    public $isEarlyExit;

    public static $tableName = 'attendance_penalty_rules';
    public static $fieldsMap = [
        'id' => 'id',
        'ruleName' => 'rule_name',
        'companyDetails' => 'company_id',
        'companyEmployee' => 'created_by',
        'minutes' => 'minutes',
        'deductionType' => 'deduction_type',
        'amount' => 'amount',
        'count' => 'count',
        'isEarlyExit' => 'is_early_exit',
    ];
}
