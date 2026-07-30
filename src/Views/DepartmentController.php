<?php
namespace Common\Views;

use Common\Services\DepartmentService;
use Common\Response\ApiResponse;
use Exception;

class DepartmentController {
    private $service;

    public function __construct() {
        $this->service = new DepartmentService();
    }

    public function get_all_department($companyId) {
        try {
            $company_id = (int)$companyId;
            $result = $this->service->get_all_departments($company_id);
            return ApiResponse::send(200, "Fetch departments details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch departments details", []);
        }
    }

    public function get_department($id) {
        try {
            $dept_id = (int)$id;
            $result = $this->service->get_department($dept_id);
            return ApiResponse::send(200, "Fetch department details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch department details", []);
        }
    }

    public function create_department() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_department($input);
            return ApiResponse::send(201, "Department details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to create department details", []);
        }
    }

    public function update_department($id) {
        try {
            $dept_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_department($dept_id, $input);
            return ApiResponse::send(200, "Department details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update department details", []);
        }
    }

    public function delete_department($id) {
        try {
            $dept_id = (int)$id;
            $this->service->delete_department($dept_id);
            return ApiResponse::send(200, "Department deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete department details", []);
        }
    }
}
