<?php
namespace Common\Models;

class CountryToState {
    public $id;
    public $country;
    public $stateCapital;
    public $stateLong;
    public $stateShort;

    public static $tableName = 'country_to_state';
    public static $fieldsMap = [
        'id' => 'country_to_state_id',
        'country' => 'fk_country_id',
        'stateCapital' => 'state_capital',
        'stateLong' => 'state_long',
        'stateShort' => 'state_short',
    ];
}
