<?php
namespace Common\Serializers;

class AttendancePenaltyRulesSerializer {
    public $id;
    public $ruleName;
    public $companyId;
    public $createdBy;
    public $createdByUserName;
    public $minutes;
    public $deductionType;
    public $amount;
    public $count;
    public $isEarlyExit;
}
