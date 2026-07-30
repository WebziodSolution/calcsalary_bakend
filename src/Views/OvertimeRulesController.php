<?php
namespace Common\Views;

use Common\Services\OvertimeRulesService;
use Common\Response\ApiResponse;
use Exception;

class OvertimeRulesController {
    private $service;

    public function __construct() {
        $this->service = new OvertimeRulesService();
    }

    public function get_all_overtime_rules($id) {
        try {
            $company_id = (int)$id;
            $result = $this->service->get_all_overtime_rules($company_id);
            return ApiResponse::send(200, "Fetch overtime rules successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch overtime rules", []);
        }
    }

    public function get_overtime_rule($id) {
        try {
            $rule_id = (int)$id;
            $result = $this->service->get_overtime_rule($rule_id);
            return ApiResponse::send(200, "Fetch overtime rules successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch overtime rules", []);
        }
    }

    public function create_overtime_rule($id) {
        try {
            $company_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_overtime_rule($input, $company_id);
            return ApiResponse::send(201, "Overtime rule created successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update_overtime_rule($id) {
        try {
            $rule_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_overtime_rule($rule_id, $input);
            return ApiResponse::send(200, "Overtime rule updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_overtime_rule($id) {
        try {
            $rule_id = (int)$id;
            $this->service->delete_overtime_rule($rule_id);
            return ApiResponse::send(200, "Overtime rule deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }
}
