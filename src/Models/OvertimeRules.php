<?php
namespace Common\Models;

class OvertimeRules {
    public $id;
    public $companyDetails;
    public $companyEmployee;
    public $ruleName;
    public $otMinutes;
    public $otAmount;
    public $otType;

    public static $tableName = 'overtime_rules';
    public static $fieldsMap = [
        'id' => 'id',
        'companyDetails' => 'company_id',
        'companyEmployee' => 'created_by',
        'ruleName' => 'rule_name',
        'otMinutes' => 'ot_minutes',
        'otAmount' => 'ot_amount',
        'otType' => 'ot_type',
    ];
}
