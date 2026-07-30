<?php
namespace Common\Specifications;

class UserInOutSpecification {
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

    public static function has_user_id($user_id) {
        return [
            "sql" => "employeeId = :user_id",
            "params" => ["user_id" => $user_id]
        ];
    }

    public static function user_id_in($user_ids) {
        $placeholders = [];
        $params = [];
        foreach ($user_ids as $idx => $id) {
            $key = "user_id_in_" . $idx;
            $placeholders[] = ":" . $key;
            $params[$key] = $id;
        }
        $in_sql = implode(", ", $placeholders);
        return [
            "sql" => "employeeId IN ($in_sql)",
            "params" => $params
        ];
    }

    public static function has_location_id($location_ids) {
        $placeholders = [];
        $params = [];
        foreach ($location_ids as $idx => $id) {
            $key = "loc_id_in_" . $idx;
            $placeholders[] = ":" . $key;
            $params[$key] = $id;
        }
        $in_sql = implode(", ", $placeholders);
        return [
            "sql" => "locations_id IN ($in_sql)",
            "params" => $params
        ];
    }

    public static function has_department_ids($department_ids) {
        // Query company_employees to match user department ids, or build a subquery.
        // For simplicity and performance, we can build a subquery:
        $placeholders = [];
        $params = [];
        foreach ($department_ids as $idx => $id) {
            $key = "dept_id_in_" . $idx;
            $placeholders[] = ":" . $key;
            $params[$key] = $id;
        }
        $in_sql = implode(", ", $placeholders);
        return [
            "sql" => "employeeId IN (SELECT id FROM company_employees WHERE department_id IN ($in_sql))",
            "params" => $params
        ];
    }

    public static function has_company($company_id) {
        return [
            "sql" => "companyDetails_id = :company_id",
            "params" => ["company_id" => $company_id]
        ];
    }

    public static function is_salary_generate() {
        return [
            "sql" => "isSalaryGenerate = 0",
            "params" => []
        ];
    }
}
