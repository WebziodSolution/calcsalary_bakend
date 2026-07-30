<?php
namespace Common\Models;

class Roles {
    public $roleId;
    public $roleName;

    public static $tableName = 'roles';
    public static $fieldsMap = [
        'roleId' => 'role_Id',
        'roleName' => 'role_name',
    ];
}
