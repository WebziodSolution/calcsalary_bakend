<?php
namespace Common\Specifications;

class SalaryStatementHistorySpecification {
    public static function has_user_ids($user_ids) {
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
            "sql" => "departmentId IN ($in_sql)",
            "params" => $params
        ];
    }

    public static function has_month($month) {
        $placeholders = [];
        $params = [];
        foreach ($month as $idx => $m) {
            $key = "month_in_" . $idx;
            $placeholders[] = ":" . $key;
            $params[$key] = $m;
        }
        $in_sql = implode(", ", $placeholders);
        return [
            "sql" => "monthYear IN ($in_sql)",
            "params" => $params
        ];
    }

    public static function has_company($company_id) {
        return [
            "sql" => "companyDetails_id = :company_id",
            "params" => ["company_id" => $company_id]
        ];
    }
}
