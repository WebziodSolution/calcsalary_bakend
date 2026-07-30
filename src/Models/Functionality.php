<?php
namespace Common\Models;

class Functionality {
    public $id;
    public $functionalityName;

    public static $tableName = 'functionality';
    public static $fieldsMap = [
        'id' => 'id',
        'functionalityName' => 'functionality_name',
    ];
}
