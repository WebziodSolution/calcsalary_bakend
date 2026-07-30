<?php
namespace Common\Models;

class UserShift {
    public $id;
    public $shiftName;

    public static $tableName = 'user_shift';
    public static $fieldsMap = [
        'id' => 'id',
        'shiftName' => 'shift_name',
    ];
}
