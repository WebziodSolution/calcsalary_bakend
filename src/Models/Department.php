<?php
namespace Common\Models;

class Department {
    public $id;
    public $departmentName;
    public $companyDetails;

    public static $tableName = 'departments';
    public static $fieldsMap = [
        'id' => 'id',
        'departmentName' => 'department_name',
        'companyDetails' => 'company_id',
    ];
}
