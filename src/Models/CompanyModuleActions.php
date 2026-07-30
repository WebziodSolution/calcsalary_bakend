<?php
namespace Common\Models;

class CompanyModuleActions {
    public $moduleActionId;
    public $module;
    public $action;

    public static $tableName = 'company_module_actions';
    public static $fieldsMap = [
        'moduleActionId' => 'id',
        'module' => 'module_id',
        'action' => 'action_id',
    ];
}
