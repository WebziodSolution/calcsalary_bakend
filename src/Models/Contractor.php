<?php
namespace Common\Models;

class Contractor {
    public $id;
    public $contractorName;

    public static $tableName = 'contractor';
    public static $fieldsMap = [
        'id' => 'id',
        'contractorName' => 'contractor_name',
    ];
}
