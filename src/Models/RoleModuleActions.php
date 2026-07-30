<?php
namespace Common\Models;

class RoleModuleActions {
    public $roleActionId;
    public $role;
    public $moduleActions;

    public static $tableName = 'role_module_actions';
    public static $fieldsMap = [
        'roleActionId' => 'role_action_Id',
        'role' => 'role_id',
        'moduleActions' => 'module_action_Id',
    ];
}
