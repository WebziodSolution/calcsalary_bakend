<?php
namespace Common\Models;

class LeaveType {
    public $id;
    public $name;
    public $companyDetails;

    public static $tableName = 'leave_type';
    public static $fieldsMap = [
        'id' => 'id',
        'name' => 'name',
        'companyDetails' => 'company_id',
    ];
}
