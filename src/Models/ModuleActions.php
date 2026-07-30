<?php
namespace Common\Models;

class ModuleActions {
    public $moduleActionId;
    public $module;
    public $action;

    public static $tableName = 'module_actions';
    public static $fieldsMap = [
        'moduleActionId' => 'module_action_Id',
        'module' => 'module_id',
        'action' => 'action_id',
    ];
}
