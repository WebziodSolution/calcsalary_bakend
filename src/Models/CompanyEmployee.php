<?php
namespace Common\Models;

class CompanyEmployee {
    public $employeeId;
    public $companyDetails;
    public $roles;
    public $userName;
    public $firstName;
    public $lastName;
    public $email;
    public $password;
    public $phone;
    public $emergencyPhone;
    public $altPhone;
    public $profileImage;
    public $city;
    public $state;
    public $country;
    public $hourlyRate;
    public $address1;
    public $address2;
    public $gender;
    public $zipCode;
    public $dob;
    public $middleName;
    public $emergencyContact;
    public $contactPhone;
    public $relationship;
    public $companyShift;
    public $department;
    public $employeeType;
    public $payPeriod;
    public $hiredDate;
    public $isActive;
    public $companyLocation;
    public $checkGeofence;
    public $embedding;
    public $bloodGroup;
    public $aadharImage;
    public $isPf;
    public $pfType;
    public $pfPercentage;
    public $isPt;
    public $ptAmount;
    public $pfAmount;
    public $basicSalary;
    public $grossSalary;
    public $canteenType;
    public $canteenAmount;
    public $lunchBreak;
    public $workingHoursIncludeLunch;
    public $overtimeRules;
    public $weeklyOff;
    public $holidayTemplates;
    public $lateEntryPenaltyRule;
    public $earlyExitPenaltyRule;

    public static $tableName = 'company_employees';
    public static $fieldsMap = [
        'employeeId' => 'id',
        'companyDetails' => 'company_id',
        'roles' => 'role_id',
        'userName' => 'user_name',
        'firstName' => 'first_name',
        'lastName' => 'last_name',
        'email' => 'email',
        'password' => 'password',
        'phone' => 'phone',
        'emergencyPhone' => 'emergency_phone',
        'altPhone' => 'alt_phone',
        'profileImage' => 'profile_image',
        'city' => 'city',
        'state' => 'state',
        'country' => 'country',
        'hourlyRate' => 'hourly_rate',
        'address1' => 'address1',
        'address2' => 'address2',
        'gender' => 'gender',
        'zipCode' => 'zip_code',
        'dob' => 'dob',
        'middleName' => 'middle_name',
        'emergencyContact' => 'emergency_contact',
        'contactPhone' => 'contact_phone',
        'relationship' => 'relationship',
        'companyShift' => 'shift_id',
        'department' => 'department_id',
        'employeeType' => 'employee_type',
        'payPeriod' => 'pay_period',
        'hiredDate' => 'hired_date',
        'isActive' => 'is_active',
        'companyLocation' => 'company_location',
        'checkGeofence' => 'check_geofence',
        'embedding' => 'embedding',
        'bloodGroup' => 'blood_group',
        'aadharImage' => 'aadhar_image',
        'isPf' => 'is_pf',
        'pfType' => 'pf_type',
        'pfPercentage' => 'pf_percentage',
        'isPt' => 'is_pt',
        'ptAmount' => 'pt_amount',
        'pfAmount' => 'pf_amount',
        'basicSalary' => 'basic_salary',
        'grossSalary' => 'gross_salary',
        'canteenType' => 'canteen_type',
        'canteenAmount' => 'canteen_amount',
        'lunchBreak' => 'lunch_break',
        'workingHoursIncludeLunch' => 'working_hours_include_lunch',
        'overtimeRules' => 'ot_id',
        'weeklyOff' => 'weekly_off',
        'holidayTemplates' => 'holiday_template',
        'lateEntryPenaltyRule' => 'late_entry_penalty_rule',
        'earlyExitPenaltyRule' => 'early_exit_penalty_rule',
    ];
}
