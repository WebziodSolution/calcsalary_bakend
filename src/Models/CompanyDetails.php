<?php
namespace Common\Models;

class CompanyDetails {
    public $id;
    public $companyNo;
    public $companyName;
    public $dba;
    public $companyLogo;
    public $email;
    public $phone;
    public $industryName;
    public $websiteUrl;
    public $isActive;
    public $registerDate;
    public $ein;
    public $organizationType;
    public $autoTimeInAfterHours;

    public static $tableName = 'company_details';
    public static $fieldsMap = [
        'id' => 'id',
        'companyNo' => 'company_no',
        'companyName' => 'company_name',
        'dba' => 'DBA',
        'companyLogo' => 'company_logo',
        'email' => 'email',
        'phone' => 'phone',
        'industryName' => 'industry_name',
        'websiteUrl' => 'website_url',
        'isActive' => 'is_active',
        'registerDate' => 'register_date',
        'ein' => 'EIN',
        'organizationType' => 'organization_type',
        'autoTimeInAfterHours' => 'auto_time_in_after_hours',
    ];
}
