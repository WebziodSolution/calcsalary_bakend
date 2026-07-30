<?php
namespace Common\Models;

class CompanyActions {
    public $actionId;
    public $actionName;

    public static $tableName = 'company_actions';
    public static $fieldsMap = [
        'actionId' => 'id',
        'actionName' => 'action_name',
    ];
}
