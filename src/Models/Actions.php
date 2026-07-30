<?php
namespace Common\Models;

class Actions {
    public $actionId;
    public $actionName;

    public static $tableName = 'actions';
    public static $fieldsMap = [
        'actionId' => 'action_Id',
        'actionName' => 'action_name',
    ];
}
