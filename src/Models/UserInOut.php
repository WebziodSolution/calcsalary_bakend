<?php
namespace Common\Models;

class UserInOut {
    public $id;
    public $timeIn;
    public $timeOut;
    public $createdOn;
    public $user;
    public $locations;
    public $companyDetails;
    public $isSalaryGenerate;

    public static $tableName = 'user_inout';
    public static $fieldsMap = [
        'id' => 'id',
        'timeIn' => 'time_in',
        'timeOut' => 'time_out',
        'createdOn' => 'created_on',
        'user' => 'user_id',
        'locations' => 'location_id',
        'companyDetails' => 'company_id',
        'isSalaryGenerate' => 'is_salary_generate',
    ];
}
