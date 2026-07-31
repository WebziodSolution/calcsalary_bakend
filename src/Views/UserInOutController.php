<?php
namespace Common\Views;

use Common\Services\UserInOutService;
use Common\Response\ApiResponse;
use Exception;

class UserInOutController {
    private $service;

    public function __construct() {
        $this->service = new UserInOutService();
    }

    public function get_report() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            
            $user_ids = [];
            if (!empty($input['userIds'])) {
                $raw = (array)$input['userIds'];
                foreach ($raw as $val) {
                    if (strpos((string)$val, ",") !== false) {
                        $parts = explode(",", (string)$val);
                        foreach ($parts as $p) {
                            if (trim($p) !== "") {
                                $user_ids[] = (int)trim($p);
                            }
                        }
                    } else {
                        $user_ids[] = (int)$val;
                    }
                }
            }

            $start_date = $input['startDate'] ?? null;
            $end_date = $input['endDate'] ?? null;
            $time_zone = $input['timeZone'] ?? null;
            $company_id = !empty($input['companyId']) ? (int)$input['companyId'] : null;

            $result = $this->service->get_time_inout_report($user_ids, $start_date, $end_date, $time_zone, $company_id);
            return ApiResponse::send(200, "InOut's Report fetched successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Failed to Fetch InOut's Report", ["error" => $e->getMessage()]);
        }
    }

    public function generate_excel_report() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            
            $user_ids = [];
            if (!empty($input['userIds'])) {
                $raw = (array)$input['userIds'];
                foreach ($raw as $val) {
                    if (strpos((string)$val, ",") !== false) {
                        $parts = explode(",", (string)$val);
                        foreach ($parts as $p) {
                            if (trim($p) !== "") {
                                $user_ids[] = (int)trim($p);
                            }
                        }
                    } else {
                        $user_ids[] = (int)$val;
                    }
                }
            }

            $start_date = $input['startDate'] ?? null;
            $end_date = $input['endDate'] ?? null;
            $time_zone = $input['timeZone'] ?? null;
            $company_id = !empty($input['companyId']) ? (int)$input['companyId'] : null;

            $report_data = $this->service->get_time_inout_report($user_ids, $start_date, $end_date, $time_zone, $company_id);
            $xml_content = $this->service->generate_excel_report($report_data, $start_date, $end_date, $time_zone);

            if (ob_get_level()) {
                ob_clean();
            }
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="InOutReport.xls"');
            echo $xml_content;
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            exit;
        }
    }

    public function get_dashboard_data($companyId) {
        try {
            $result = $this->service->dashboard_counts((int)$companyId);
            return ApiResponse::send(200, "Current In-Users fetched successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to get userInOut", []);
        }
    }

    public function get_user_last_inout($userId) {
        try {
            $result = $this->service->get_user_last_inout((int)$userId);
            return ApiResponse::send(200, "User fetched successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to get userInOut", []);
        }
    }

    public function get_all_records() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            
            $user_ids = [];
            if (!empty($input['userIds'])) {
                $raw = (array)$input['userIds'];
                foreach ($raw as $val) {
                    if (strpos((string)$val, ",") !== false) {
                        $parts = explode(",", (string)$val);
                        foreach ($parts as $p) {
                            if (trim($p) !== "") {
                                $user_ids[] = (int)trim($p);
                            }
                        }
                    } else {
                        $user_ids[] = (int)$val;
                    }
                }
            }

            $location_ids = [];
            if (!empty($input['locationIds'])) {
                $raw = (array)$input['locationIds'];
                foreach ($raw as $val) {
                    if (strpos((string)$val, ",") !== false) {
                        $parts = explode(",", (string)$val);
                        foreach ($parts as $p) {
                            if (trim($p) !== "") {
                                $location_ids[] = (int)trim($p);
                            }
                        }
                    } else {
                        $location_ids[] = (int)$val;
                    }
                }
            }

            $dept_ids = [];
            if (!empty($input['departmentIds'])) {
                $raw = (array)$input['departmentIds'];
                foreach ($raw as $val) {
                    if (strpos((string)$val, ",") !== false) {
                        $parts = explode(",", (string)$val);
                        foreach ($parts as $p) {
                            if (trim($p) !== "") {
                                $dept_ids[] = (int)trim($p);
                            }
                        }
                    } else {
                        $dept_ids[] = (int)$val;
                    }
                }
            }

            $start_date = $input['startDate'] ?? null;
            $end_date = $input['endDate'] ?? null;
            $time_zone = $input['timeZone'] ?? null;
            $company_id = !empty($input['companyId']) ? (int)$input['companyId'] : null;

            $result = $this->service->get_all_entries_by_user_id($user_ids, $start_date, $end_date, $time_zone, $location_ids, $dept_ids, $company_id);
            return ApiResponse::send(200, "UserInOut fetched successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to get userInOut", []);
        }
    }

    public function get_all_records_grouped_by_user() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            
            $user_ids = [];
            if (!empty($input['userIds'])) {
                $raw = (array)$input['userIds'];
                foreach ($raw as $val) {
                    if (strpos((string)$val, ",") !== false) {
                        $parts = explode(",", (string)$val);
                        foreach ($parts as $p) {
                            if (trim($p) !== "") {
                                $user_ids[] = (int)trim($p);
                            }
                        }
                    } else {
                        $user_ids[] = (int)$val;
                    }
                }
            }

            $location_ids = [];
            if (!empty($input['locationIds'])) {
                $raw = (array)$input['locationIds'];
                foreach ($raw as $val) {
                    if (strpos((string)$val, ",") !== false) {
                        $parts = explode(",", (string)$val);
                        foreach ($parts as $p) {
                            if (trim($p) !== "") {
                                $location_ids[] = (int)trim($p);
                            }
                        }
                    } else {
                        $location_ids[] = (int)$val;
                    }
                }
            }

            $dept_ids = [];
            if (!empty($input['departmentIds'])) {
                $raw = (array)$input['departmentIds'];
                foreach ($raw as $val) {
                    if (strpos((string)$val, ",") !== false) {
                        $parts = explode(",", (string)$val);
                        foreach ($parts as $p) {
                            if (trim($p) !== "") {
                                $dept_ids[] = (int)trim($p);
                            }
                        }
                    } else {
                        $dept_ids[] = (int)$val;
                    }
                }
            }

            $start_date = $input['startDate'] ?? null;
            $end_date = $input['endDate'] ?? null;
            $time_zone = $input['timeZone'] ?? null;
            $company_id = !empty($input['companyId']) ? (int)$input['companyId'] : null;

            $result = $this->service->get_all_records_grouped_by_user($user_ids, $start_date, $end_date, $time_zone, $location_ids, $dept_ids, $company_id);
            return ApiResponse::send(200, "UserInOut fetched successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to get userInOut", []);
        }
    }

    public function get_today_records() {
        try {
            $user_id = $_SERVER['AUTH_USER_ID'] ?? null;
            if (!$user_id) {
                return ApiResponse::send(401, "Unauthorized", []);
            }
            $result = $this->service->get_today_entries_by_user_id((int)$user_id);
            return ApiResponse::send(200, "UserInOut fetched successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to get userInOut", []);
        }
    }

    public function get_user_inout($id) {
        try {
            $result = $this->service->get_user_inout((int)$id);
            return ApiResponse::send(200, "UserInOut fetched successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to get userInOut", []);
        }
    }

    public function create_user_inout() {
        try {
            $user_id = $_SERVER['AUTH_USER_ID'] ?? null;
            if (!$user_id) {
                return ApiResponse::send(401, "Unauthorized", []);
            }
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $location_id_raw = $input['locationId'] ?? null;
            $company_id_raw = $input['companyId'] ?? null;

            $parsed_user_id = (int)$user_id;
            
            $parsed_location_id = null;
            if ($location_id_raw !== null && $location_id_raw !== "undefined" && trim((string)$location_id_raw) !== "") {
                $parsed_location_id = (int)$location_id_raw;
            }

            $parsed_company_id = null;
            if ($company_id_raw !== null && $company_id_raw !== "undefined" && trim((string)$company_id_raw) !== "") {
                $parsed_company_id = (int)$company_id_raw;
            }

            $result = $this->service->create_user_inout($parsed_user_id, $parsed_location_id, $parsed_company_id);
            return ApiResponse::send(201, "UserInOut added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to create userInOut", []);
        }
    }

    public function update_user_inout_by_id($id) {
        try {
            $user_id = $_SERVER['AUTH_USER_ID'] ?? null;
            if (!$user_id) {
                return ApiResponse::send(401, "Unauthorized", "");
            }
            $this->service->update_user_inout_by_id((int)$id, (int)$user_id);
            return ApiResponse::send(200, "UserInOut updated successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update userInOut details", []);
        }
    }

    public function update_user_inout_by_dto() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $this->service->update_user_inout_by_dto($input);
            return ApiResponse::send(200, "UserInOut updated successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update userInOut details", []);
        }
    }

    public function clock_in_out() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $employee_id = $input['employeeId'] ?? null;
            $location_id_raw = $input['locationId'] ?? null;
            $company_id = $input['companyId'] ?? null;

            if (!$employee_id || !$company_id) {
                return ApiResponse::send(400, "Missing employeeId or companyId", []);
            }

            $parsed_location_id = null;
            if ($location_id_raw !== null && $location_id_raw !== "undefined" && trim((string)$location_id_raw) !== "") {
                $parsed_location_id = (int)$location_id_raw;
            }

            $res = $this->service->click_in_out((int)$employee_id, $parsed_location_id, (int)$company_id);
            $parts = explode(":", $res, 2);
            $res_status = $parts[0];
            $username = isset($parts[1]) ? $parts[1] : "";

            if ($res_status === "created") {
                return ApiResponse::send(201, $username . " clock in successfully", "");
            } else {
                return ApiResponse::send(200, $username . " clock out successfully", "");
            }
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to create userInOut", []);
        }
    }

    public function add_clock_in_out() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->add_clock_in_out($input);
            return ApiResponse::send(201, "UserInOut added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to create userInOut", []);
        }
    }

    public function delete_user_inout($id) {
        try {
            $this->service->delete_user_inout((int)$id);
            return ApiResponse::send(200, "UserInOut deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete userInOut record", []);
        }
    }

    public function add_bulk_clock_in_out() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $this->service->add_bulk_clock_in_out($input);
            return ApiResponse::send(201, "Bulk UserInOut added successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to bulk create userInOut", ["error" => $e->getMessage()]);
        }
    }
}
