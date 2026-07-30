<?php
namespace Common\Specifications;

class EmployeeStatementSpecification {
    public static function match_created_on($date) {
        return [
            "sql" => "createdOn = :created_on",
            "params" => ["created_on" => $date]
        ];
    }

    public static function between_created_on($start_date, $end_date) {
        return [
            "sql" => "createdOn BETWEEN :start_date AND :end_date",
            "params" => [
                "start_date" => $start_date,
                "end_date" => $end_date
            ]
        ];
    }

    public static function created_on_greater_than_equal($start_date) {
        return [
            "sql" => "createdOn >= :created_on_gte",
            "params" => ["created_on_gte" => $start_date]
        ];
    }

    public static function created_on_less_than_equal($end_date) {
        return [
            "sql" => "createdOn <= :created_on_lte",
            "params" => ["created_on_lte" => $end_date]
        ];
    }

    public static function has_user_ids($user_ids) {
        // Since we are building standard IN queries in Core PHP:
        $placeholders = [];
        $params = [];
        foreach ($user_ids as $idx => $id) {
            $key = "user_id_in_" . $idx;
            $placeholders[] = ":" . $key;
            $params[$key] = $id;
        }
        $in_sql = implode(", ", $placeholders);
        return [
            "sql" => "employee_id IN ($in_sql)", // Mapping Django user__employeeId__in
            "params" => $params
        ];
    }

    public static function has_department_ids($department_ids) {
        $placeholders = [];
        $params = [];
        foreach ($department_ids as $idx => $id) {
            $key = "dept_id_in_" . $idx;
            $placeholders[] = ":" . $key;
            $params[$key] = $id;
        }
        $in_sql = implode(", ", $placeholders);
        return [
            "sql" => "department_id IN ($in_sql)",
            "params" => $params
        ];
    }

    public static function has_employee_ids($employee_ids) {
        $placeholders = [];
        $params = [];
        foreach ($employee_ids as $idx => $id) {
            $key = "emp_id_in_" . $idx;
            $placeholders[] = ":" . $key;
            $params[$key] = $id;
        }
        $in_sql = implode(", ", $placeholders);
        return [
            "sql" => "employee_id IN ($in_sql)",
            "params" => $params
        ];
    }

    public static function has_company_id($company_id) {
        return [
            "sql" => "company_id = :company_id",
            "params" => ["company_id" => $company_id]
        ];
    }
}
