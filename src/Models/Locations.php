<?php
namespace Common\Models;

class Locations {
    public $id;
    public $locationName;
    public $city;
    public $state;
    public $country;
    public $address1;
    public $address2;
    public $zipCode;
    public $employeeCount;
    public $externalId;
    public $geofenceId;
    public $isActive;
    public $payPeriod;
    public $payPeriodStart;
    public $payPeriodEnd;
    public $companyDetails;

    public static $tableName = 'locations';
    public static $fieldsMap = [
        'id' => 'id',
        'locationName' => 'location_name',
        'city' => 'city',
        'state' => 'state',
        'country' => 'country',
        'address1' => 'address1',
        'address2' => 'address2',
        'zipCode' => 'zip_code',
        'employeeCount' => 'employee_count',
        'externalId' => 'radar_external_id',
        'geofenceId' => 'geofence_Id',
        'isActive' => 'is_active',
        'payPeriod' => 'pay_period',
        'payPeriodStart' => 'pay_period_start',
        'payPeriodEnd' => 'pay_period_end',
        'companyDetails' => 'company_id',
    ];
}
