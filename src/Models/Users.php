<?php
namespace Common\Models;

class Users {
    public $userId;
    public $firstName;
    public $lastName;
    public $middleName;
    public $email;
    public $phone;
    public $password;
    public $personalIdentificationNumber;
    public $gender;
    public $hourlyRate;
    public $address1;
    public $address2;
    public $city;
    public $zipCode;
    public $country;
    public $state;
    public $birthDate;
    public $emergencyContact;
    public $contactPhone;
    public $relationship;
    public $department;
    public $role;
    public $userShift;
    public $contractor;
    public $profileImage;
    public $employeeId;
    public $userName;

    public static $tableName = 'users';
    public static $fieldsMap = [
        'userId' => 'user_Id',
        'firstName' => 'first_name',
        'lastName' => 'last_name',
        'middleName' => 'middle_name',
        'email' => 'email',
        'phone' => 'phone',
        'password' => 'password',
        'personalIdentificationNumber' => 'personal_identification_number',
        'gender' => 'gender',
        'hourlyRate' => 'hourly_rate',
        'address1' => 'address1',
        'address2' => 'address2',
        'city' => 'city',
        'zipCode' => 'zip_code',
        'country' => 'country',
        'state' => 'state',
        'birthDate' => 'birth_date',
        'emergencyContact' => 'emergency_contact',
        'contactPhone' => 'contact_phone',
        'relationship' => 'relationship',
        'department' => 'department_id',
        'role' => 'role_id',
        'userShift' => 'user_shift_id',
        'contractor' => 'contractor_id',
        'profileImage' => 'profile_img',
        'employeeId' => 'employee_id',
        'userName' => 'user_name',
    ];
}
