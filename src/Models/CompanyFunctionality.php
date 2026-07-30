<?php
namespace Common\Models;

class CompanyFunctionality {
    public $id;
    public $functionalityName;

    public static $tableName = 'company_functionality';
    public static $fieldsMap = [
        'id' => 'id',
        'functionalityName' => 'functionality_name',
    ];
}
