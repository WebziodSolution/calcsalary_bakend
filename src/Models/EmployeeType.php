<?php
namespace Common\Models;

class EmployeeType {
    public $id;
    public $name;

    public static $tableName = 'employee_type';
    public static $fieldsMap = [
        'id' => 'id',
        'name' => 'name',
    ];
}
