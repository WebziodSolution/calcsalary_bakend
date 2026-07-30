<?php
namespace Common\Views;

use Common\Services\LeaveTypeService;
use Common\Response\ApiResponse;
use Exception;

class LeaveTypeController {
    private $service;

    public function __construct() {
        $this->service = new LeaveTypeService();
    }

    public function get_all_leave_types($companyId) {
        try {
            $company_id = (int)$companyId;
            $result = $this->service->get_all_leave_types($company_id);
            return ApiResponse::send(200, "Fetch leave types successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch leave types", []);
        }
    }

    public function get_leave_type($id) {
        try {
            $leave_id = (int)$id;
            $result = $this->service->get_leave_type($leave_id);
            return ApiResponse::send(200, "Fetch leave details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch leave details", []);
        }
    }

    public function create_leave_type() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_leave_type($input);
            return ApiResponse::send(201, "Leave details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to add leave details", []);
        }
    }

    public function update_leave_type($id) {
        try {
            $leave_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_leave_type($leave_id, $input);
            return ApiResponse::send(200, "Leave details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update leave details", []);
        }
    }

    public function delete_leave_type($id) {
        try {
            $leave_id = (int)$id;
            $this->service->delete_leave_type($leave_id);
            return ApiResponse::send(200, "Leave details deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete leave details", []);
        }
    }
}
