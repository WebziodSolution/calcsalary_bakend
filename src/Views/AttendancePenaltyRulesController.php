<?php
namespace Common\Views;

use Common\Services\AttendancePenaltyRulesService;
use Common\Response\ApiResponse;
use Exception;

class AttendancePenaltyRulesController {
    private $service;

    public function __construct() {
        $this->service = new AttendancePenaltyRulesService();
    }

    public function get_all_by_company_id($flag, $companyId) {
        try {
            $flag_val = (int)$flag;
            $company_id = (int)$companyId;
            $result = $this->service->find_all_by_company_id($flag_val, $company_id);
            return ApiResponse::send(200, "Fetch attendance penalty rules successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch attendance penalty rules", []);
        }
    }

    public function get_by_id($id) {
        try {
            $rule_id = (int)$id;
            $result = $this->service->find_by_id($rule_id);
            return ApiResponse::send(200, "Fetch attendance rule successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch attendance penalty rule", []);
        }
    }

    public function create() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $input['createdBy'] = $_SERVER['AUTH_USER_ID'] ?? null;
            $result = $this->service->create($input);
            return ApiResponse::send(201, "Attendance penalty rules created successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update($id) {
        try {
            $rule_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $input['createdBy'] = $_SERVER['AUTH_USER_ID'] ?? null;
            $result = $this->service->update($rule_id, $input);
            return ApiResponse::send(200, "Attendance penalty rules update successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete($id) {
        try {
            $rule_id = (int)$id;
            $this->service->delete_by_id($rule_id);
            return ApiResponse::send(200, "Attendance penalty rule deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete attendance penalty rule", []);
        }
    }
}
