<?php
namespace Common\Views;

use Common\Services\EmployeeTypeService;
use Common\Response\ApiResponse;
use Exception;

class EmployeeTypeController {
    private $service;

    public function __construct() {
        $this->service = new EmployeeTypeService();
    }

    public function get_all_employee_types() {
        try {
            $result = $this->service->get_all_employee_types();
            return ApiResponse::send(200, "Fetch employee type details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch employee type details", []);
        }
    }

    public function get_employee_type($id) {
        try {
            $type_id = (int)$id;
            $result = $this->service->get_employee_type($type_id);
            return ApiResponse::send(200, "Fetch employee type details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch employee type details", []);
        }
    }

    public function create_employee_type() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_employee_type($input);
            return ApiResponse::send(201, "Employee type details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to add employee type details", []);
        }
    }

    public function update_employee_type($id) {
        try {
            $type_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_employee_type($type_id, $input);
            return ApiResponse::send(200, "Employee type details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update employee type details", []);
        }
    }

    public function delete_employee_type($id) {
        try {
            $type_id = (int)$id;
            $this->service->delete_employee_type($type_id);
            return ApiResponse::send(200, "Employee type details deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete employee type details", []);
        }
    }
}
