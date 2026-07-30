<?php
namespace Common\Views;

use Common\Services\CompanyEmployeeRoleService;
use Common\Response\ApiResponse;
use Exception;

class CompanyEmployeeRoleController {
    private $service;

    public function __construct() {
        $this->service = new CompanyEmployeeRoleService();
    }

    public function get_all_roles_list() {
        try {
            $result = $this->service->get_all_roles_list();
            return ApiResponse::send(200, "Fetch roles list details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch roles list details", []);
        }
    }

    public function roles_list() {
        try {
            $search_key = $_GET['searchKey'] ?? "";
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
            $size = isset($_GET['size']) ? (int)$_GET['size'] : 10;

            $result = $this->service->roles_list($search_key, $page, $size);
            return ApiResponse::send(200, "Fetch roles list details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch roles list details", []);
        }
    }

    public function get_all_roles() {
        try {
            $result = $this->service->get_all_roles();
            return ApiResponse::send(200, "Fetch roles details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch roles details", []);
        }
    }

    public function get_actions($roleId) {
        try {
            $role_id = (int)$roleId;
            $result = $this->service->get_policy($role_id);
            return ApiResponse::send(200, "Fetch policy details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch policy details", []);
        }
    }

    public function get_all_company_employee_roles($id) {
        try {
            $company_id = (int)$id;
            $result = $this->service->get_all_roles_by_company_id($company_id);
            return ApiResponse::send(200, "Fetch company employee roles successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch company employee roles", []);
        }
    }

    public function get_employee_roles($id) {
        try {
            $role_id = (int)$id;
            $result = $this->service->get_role($role_id);
            return ApiResponse::send(200, "Fetch employee roles successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch employee roles", []);
        }
    }

    public function create_employee_roles() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_role($input);
            return ApiResponse::send(201, "Employee roles added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update_employee_roles($id) {
        try {
            $role_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_role($role_id, $input);
            return ApiResponse::send(200, "Employee roles updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_employee_roles($id) {
        try {
            $role_id = (int)$id;
            $this->service->delete_role($role_id);
            return ApiResponse::send(200, "Employee roles delete successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch delet employee roles", []);
        }
    }
}
