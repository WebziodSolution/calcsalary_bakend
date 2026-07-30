<?php
namespace Common\Models;

class WeeklyOff {
    public $id;
    public $name;
    public $description;
    public $isDefault;
    public $sundayAll;
    public $sunday1st;
    public $sunday2nd;
    public $sunday3rd;
    public $sunday4th;
    public $sunday5th;
    public $mondayAll;
    public $monday1st;
    public $monday2nd;
    public $monday3rd;
    public $monday4th;
    public $monday5th;
    public $tuesdayAll;
    public $tuesday1st;
    public $tuesday2nd;
    public $tuesday3rd;
    public $tuesday4th;
    public $tuesday5th;
    public $wednesdayAll;
    public $wednesday1st;
    public $wednesday2nd;
    public $wednesday3rd;
    public $wednesday4th;
    public $wednesday5th;
    public $thursdayAll;
    public $thursday1st;
    public $thursday2nd;
    public $thursday3rd;
    public $thursday4th;
    public $thursday5th;
    public $fridayAll;
    public $friday1st;
    public $friday2nd;
    public $friday3rd;
    public $friday4th;
    public $friday5th;
    public $saturdayAll;
    public $saturday1st;
    public $saturday2nd;
    public $saturday3rd;
    public $saturday4th;
    public $saturday5th;
    public $companyEmployee;
    public $companyDetails;

    public static $tableName = 'weekly_off';
    public static $fieldsMap = [
        'id' => 'id',
        'name' => 'name',
        'description' => 'description',
        'isDefault' => 'is_default',
        'sundayAll' => 'sunday_all',
        'sunday1st' => 'sunday_1st',
        'sunday2nd' => 'sunday_2nd',
        'sunday3rd' => 'sunday_3rd',
        'sunday4th' => 'sunday_4th',
        'sunday5th' => 'sunday_5th',
        'mondayAll' => 'monday_all',
        'monday1st' => 'monday_1st',
        'monday2nd' => 'monday_2nd',
        'monday3rd' => 'monday_3rd',
        'monday4th' => 'monday_4th',
        'monday5th' => 'monday_5th',
        'tuesdayAll' => 'tuesday_all',
        'tuesday1st' => 'tuesday_1st',
        'tuesday2nd' => 'tuesday_2nd',
        'tuesday3rd' => 'tuesday_3rd',
        'tuesday4th' => 'tuesday_4th',
        'tuesday5th' => 'tuesday_5th',
        'wednesdayAll' => 'wednesday_all',
        'wednesday1st' => 'wednesday_1st',
        'wednesday2nd' => 'wednesday_2nd',
        'wednesday3rd' => 'wednesday_3rd',
        'wednesday4th' => 'wednesday_4th',
        'wednesday5th' => 'wednesday_5th',
        'thursdayAll' => 'thursday_all',
        'thursday1st' => 'thursday_1st',
        'thursday2nd' => 'thursday_2nd',
        'thursday3rd' => 'thursday_3rd',
        'thursday4th' => 'thursday_4th',
        'thursday5th' => 'thursday_5th',
        'fridayAll' => 'friday_all',
        'friday1st' => 'friday_1st',
        'friday2nd' => 'friday_2nd',
        'friday3rd' => 'friday_3rd',
        'friday4th' => 'friday_4th',
        'friday5th' => 'friday_5th',
        'saturdayAll' => 'saturday_all',
        'saturday1st' => 'saturday_1st',
        'saturday2nd' => 'saturday_2nd',
        'saturday3rd' => 'saturday_3rd',
        'saturday4th' => 'saturday_4th',
        'saturday5th' => 'saturday_5th',
        'companyEmployee' => 'created_by',
        'companyDetails' => 'company_id',
    ];
}
