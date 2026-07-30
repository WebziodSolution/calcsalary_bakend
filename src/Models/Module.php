<?php
namespace Common\Models;

class Module {
    public $moduleId;
    public $moduleName;
    public $functionality;

    public static $tableName = 'module';
    public static $fieldsMap = [
        'moduleId' => 'module_Id',
        'moduleName' => 'module_name',
        'functionality' => 'functionality_id',
    ];
}
