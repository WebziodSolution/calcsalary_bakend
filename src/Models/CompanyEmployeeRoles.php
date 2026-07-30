<?php
namespace Common\Models;

class CompanyEmployeeRoles {
    public $roleId;
    public $companyDetails;
    public $roleName;

    public static $tableName = 'company_employee_roles';
    public static $fieldsMap = [
        'roleId' => 'id',
        'companyDetails' => 'company_id',
        'roleName' => 'role_name',
    ];
}
