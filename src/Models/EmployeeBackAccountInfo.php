<?php
namespace Common\Models;

class EmployeeBackAccountInfo {
    public $id;
    public $accountType;
    public $ifscCode;
    public $branch;
    public $bankName;
    public $accountNumber;
    public $address;
    public $passbookImage;
    public $companyEmployee;

    public static $tableName = 'employee_backaccount_info';
    public static $fieldsMap = [
        'id' => 'id',
        'accountType' => 'account_type',
        'ifscCode' => 'ifsc_code',
        'branch' => 'branch',
        'bankName' => 'bank_name',
        'accountNumber' => 'account_number',
        'address' => 'address',
        'passbookImage' => 'passbook_image',
        'companyEmployee' => 'employee_id',
    ];
}
