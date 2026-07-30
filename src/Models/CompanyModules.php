<?php
namespace Common\Models;

class CompanyModules {
    public $moduleId;
    public $moduleName;
    public $functionality;

    public static $tableName = 'company_modules';
    public static $fieldsMap = [
        'moduleId' => 'id',
        'moduleName' => 'module_name',
        'functionality' => 'functionality_id',
    ];
}
