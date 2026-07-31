<?php
namespace Common\Services;

use Common\Services\CommonService;
use Common\Services\DbHelper;
use DateTime;
use DateTimeZone;
use Exception;

class CompanyReportService {
    private $common_service;

    public function __construct() {
        $this->common_service = new CommonService();
    }

    public function getCompanies($start_date_str, $end_date_str, $min_val, $max_val, $page, $size, $time_zone) {
        try {
            $where = [];
            $params = [];

            if ($time_zone === "Asia/Calcutta") {
                $time_zone = "Asia/Kolkata";
            }

            if ($start_date_str && $time_zone) {
                if (strpos($start_date_str, ",") !== false) {
                    $parts = explode(",", $start_date_str);
                    $start_date_str = trim($parts[0]);
                }
                $start_utc = $this->common_service->convert_local_to_utc($start_date_str, $time_zone, false);
                $where[] = "c.register_date >= :start_date";
                $params["start_date"] = $start_utc->format('Y-m-d H:i:s');
            }

            if ($end_date_str && $time_zone) {
                if (strpos($end_date_str, ",") !== false) {
                    $parts = explode(",", $end_date_str);
                    $end_date_str = trim($parts[0]);
                }
                $end_utc = $this->common_service->convert_local_to_utc($end_date_str, $time_zone, false);
                $where[] = "c.register_date <= :end_date";
                $params["end_date"] = $end_utc->format('Y-m-d H:i:s');
            }

            $sql = "SELECT * FROM (
                SELECT c.id, c.company_name, c.email, c.phone, c.register_date, 
                       (SELECT COUNT(*) FROM company_employees e WHERE e.company_id = c.id) AS employee_count
                FROM company_details c
            ) t";

            $where_clause = [];
            if (!empty($where)) {
                $where_clause[] = implode(" AND ", $where);
            }

            if ($min_val !== null && $max_val !== null) {
                $where_clause[] = "t.employee_count >= :min_val AND t.employee_count <= :max_val";
                $params["min_val"] = $min_val;
                $params["max_val"] = $max_val;
            } else if ($min_val !== null) {
                $where_clause[] = "t.employee_count >= :min_val";
                $params["min_val"] = $min_val;
            } else if ($max_val !== null) {
                $where_clause[] = "t.employee_count <= :max_val";
                $params["max_val"] = $max_val;
            }

            if (!empty($where_clause)) {
                $sql .= " WHERE " . implode(" AND ", $where_clause);
            }

            $sql .= " ORDER BY t.register_date DESC";

            // Count total rows for pagination
            $db = DbHelper::getDb();
            $count_sql = "SELECT COUNT(*) as total_rows FROM (" . $sql . ") count_t";
            $stmt = $db->prepare($count_sql);
            $stmt->execute($params);
            $total_rows = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['total_rows'];

            $limit = $size > 0 ? $size : 10;
            $total_pages = (int)ceil($total_rows / $limit);
            if ($total_pages === 0) {
                $total_pages = 1;
            }

            if ($page < 0 || $page >= $total_pages) {
                $page = 0;
            }

            $offset = $page * $limit;
            
            // Re-prepare the base query with limits for execution
            $paginated_sql = $sql . " LIMIT :limit OFFSET :offset";
            $stmt = $db->prepare($paginated_sql);
            
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $content = [];
            foreach ($rows as $row) {
                $formatted_date = null;
                if ($time_zone && !empty($row['register_date'])) {
                    $reg_dt = new DateTime($row['register_date'], new DateTimeZone("UTC"));
                    $date_string = $reg_dt->format("d/m/Y, H:i:s");
                    $formatted_date = $this->common_service->convert_utc_to_local($date_string, $time_zone);
                }

                $content[] = [
                    "id" => (int)$row['id'],
                    "companyName" => $row['company_name'],
                    "email" => $row['email'],
                    "phone" => $row['phone'],
                    "registerDate" => $formatted_date,
                    "employeeCount" => (int)$row['employee_count']
                ];
            }

            $current_page = $page;
            $has_next = ($current_page + 1) < $total_pages;
            $next_page = $has_next ? ($current_page + 1) : $current_page;
            $number_of_elements = count($content);
            $is_last = !$has_next;

            return [
                "content" => $content,
                "totalPages" => $total_pages,
                "currentPage" => $current_page,
                "nextPage" => $next_page,
                "numberOfElements" => $number_of_elements,
                "last" => $is_last,
                "sortDirection" => "DESC"
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
