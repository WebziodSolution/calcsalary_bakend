<?php
namespace Common\Models;

class Deductions {
    public $id;
    public $companyEmployee;
    public $type;
    public $label;
    public $amount;

    public static $tableName = 'deductions';
    public static $fieldsMap = [
        'id' => 'id',
        'companyEmployee' => 'employee_id',
        'type' => 'type',
        'label' => 'label',
        'amount' => 'amount',
    ];
}
