<?php
namespace Common\Views;

use Common\Services\EmployeeLeaveMasterService;
use Common\Response\ApiResponse;
use Exception;

class EmployeeLeaveMasterController {
    private $service;

    public function __construct() {
        $this->service = new EmployeeLeaveMasterService();
    }

    public function get_all_employee_leave_masters($companyId) {
        try {
            $company_id = (int)$companyId;
            $result = $this->service->get_all_employee_leave_masters($company_id);
            return ApiResponse::send(200, "Fetch employee leave master details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch employee leave master details", []);
        }
    }

    public function get_employee_leave_masters_by_employee($employeeId) {
        try {
            $employee_id = (int)$employeeId;
            $result = $this->service->get_employee_leave_masters_by_employee($employee_id);
            return ApiResponse::send(200, "Fetch employee leave master details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch employee leave master details", []);
        }
    }

    public function get_employee_leave_master($id) {
        try {
            $master_id = (int)$id;
            $result = $this->service->get_employee_leave_master($master_id);
            return ApiResponse::send(200, "Fetch employee leave master details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch employee leave master details", []);
        }
    }

    public function create_employee_leave_master() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_employee_leave_master($input);
            return ApiResponse::send(201, "Employee leave master details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to add employee leave master details", []);
        }
    }

    public function update_employee_leave_master($id) {
        try {
            $master_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_employee_leave_master($master_id, $input);
            return ApiResponse::send(200, "Employee leave master details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update employee leave master details", []);
        }
    }

    public function delete_employee_leave_master($id) {
        try {
            $master_id = (int)$id;
            $this->service->delete_employee_leave_master($master_id);
            return ApiResponse::send(200, "Employee leave master details deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete employee leave master details", []);
        }
    }
}
