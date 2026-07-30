<?php
namespace Common\Models;

class Country {
    public $id;
    public $iso2;
    public $cntName;
    public $longName;
    public $oid;
    public $cntCode;
    public $phoneMinLength;
    public $phoneMaxLength;

    public static $tableName = 'tbl_country';
    public static $fieldsMap = [
        'id' => 'id',
        'iso2' => 'iso2',
        'cntName' => 'cnt_name',
        'longName' => 'long_name',
        'oid' => 'oid',
        'cntCode' => 'cnt_code',
        'phoneMinLength' => 'phone_min_length',
        'phoneMaxLength' => 'phone_max_length',
    ];
}
