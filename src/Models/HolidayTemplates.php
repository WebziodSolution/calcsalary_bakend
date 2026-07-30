<?php
namespace Common\Models;

class HolidayTemplates {
    public $id;
    public $name;
    public $companyDetails;
    public $companyEmployee;

    public static $tableName = 'holiday_templates';
    public static $fieldsMap = [
        'id' => 'id',
        'name' => 'name',
        'companyDetails' => 'company_id',
        'companyEmployee' => 'created_by',
    ];
}
