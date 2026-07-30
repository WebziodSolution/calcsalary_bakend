<?php
namespace Common\Models;

class SalaryStatementMaster {
    public $id;
    public $companyDetails;
    public $month;
    public $year;
    public $totalSalary;
    public $totalPf;
    public $totalPt;
    public $note;

    public static $tableName = 'salary_statement_master';
    public static $fieldsMap = [
        'id' => 'id',
        'companyDetails' => 'company_id',
        'month' => 'month',
        'year' => 'year',
        'totalSalary' => 'total_salary',
        'totalPf' => 'total_pf',
        'totalPt' => 'total_pt',
        'note' => 'note',
    ];
}
