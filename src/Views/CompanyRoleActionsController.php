<?php
namespace Common\Views;

use Common\Services\CompanyRoleActionService;
use Common\Response\ApiResponse;
use Exception;

class CompanyRoleActionsController {
    private $service;

    public function __construct() {
        $this->service = new CompanyRoleActionService();
    }

    public function get_all_company_role_actions() {
        try {
            $result = $this->service->get_company_actions();
            return ApiResponse::send(200, "Fetch actions details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch actions details", []);
        }
    }

    public function get_action($id) {
        try {
            $action_id = (int)$id;
            $result = $this->service->get_actions($action_id);
            return ApiResponse::send(200, "Fetch action details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch action details", []);
        }
    }

    public function create_action() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_actions($input);
            return ApiResponse::send(200, "Actions details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update_action($id) {
        try {
            $action_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_actions($action_id, $input);
            return ApiResponse::send(200, "Actions details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_action($id) {
        try {
            $action_id = (int)$id;
            $this->service->delete_actions($action_id);
            return ApiResponse::send(200, "Actions details delete successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch delet actions details", []);
        }
    }
}
