<?php
namespace Common\Models;

class HolidayTemplateDetails {
    public $id;
    public $name;
    public $date;
    public $holidayTemplates;

    public static $tableName = 'holiday_template_details';
    public static $fieldsMap = [
        'id' => 'id',
        'name' => 'name',
        'date' => 'date',
        'holidayTemplates' => 'holiday_template_id',
    ];
}
