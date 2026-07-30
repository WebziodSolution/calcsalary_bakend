<?php
namespace Common\Specifications;

class CompanySpecification {
    public static function search_by_name($name) {
        return [
            "sql" => "companyName LIKE :name",
            "params" => ["name" => "%" . $name . "%"]
        ];
    }

    public static function register_date_greater_than_equal($date) {
        return [
            "sql" => "registerDate >= :reg_date_gte",
            "params" => ["reg_date_gte" => $date]
        ];
    }

    public static function register_date_less_than_equal($date) {
        return [
            "sql" => "registerDate <= :reg_date_lte",
            "params" => ["reg_date_lte" => $date]
        ];
    }

    public static function is_active($active) {
        return [
            "sql" => "isActive = :is_active",
            "params" => ["is_active" => $active ? 1 : 0]
        ];
    }

    public static function employee_count_between($min_val, $max_val) {
        return [
            "sql" => "employee_count >= :emp_cnt_min AND employee_count <= :emp_cnt_max",
            "params" => [
                "emp_cnt_min" => $min_val,
                "emp_cnt_max" => $max_val
            ]
        ];
    }

    public static function employee_count_greater_than($min_val) {
        return [
            "sql" => "employee_count >= :emp_cnt_min",
            "params" => ["emp_cnt_min" => $min_val]
        ];
    }

    public static function employee_count_less_than($max_val) {
        return [
            "sql" => "employee_count <= :emp_cnt_max",
            "params" => ["emp_cnt_max" => $max_val]
        ];
    }
}
