<?php
namespace Common\Models;

class CompanyRoleModuleActions {
    public $roleActionId;
    public $role;
    public $moduleActions;

    public static $tableName = 'company_role_module_actions';
    public static $fieldsMap = [
        'roleActionId' => 'id',
        'role' => 'role_id',
        'moduleActions' => 'module_action_Id',
    ];
}
