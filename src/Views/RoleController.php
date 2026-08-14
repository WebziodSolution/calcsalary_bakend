<?php
namespace Common\Views;

use Common\Services\RoleService;
use Common\Response\ApiResponse;
use Exception;

class RoleController {
    private $service;

    public function __construct() {
        $this->service = new RoleService();
    }

    public function get_all_roles_list() {
        try {
            $result = $this->service->getAllRolesList();
            return ApiResponse::send(200, "Fetch roles details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch roles details", []);
        }
    }

    public function create_role() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->createRole($input);
            return ApiResponse::send(200, "Roles details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function roles_list() {
        try {
            $search_key = $_GET['searchKey'] ?? "";
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
            $size = isset($_GET['size']) ? (int)$_GET['size'] : 10;

            $result = $this->service->rolesList($search_key, $page, $size);
            return ApiResponse::send(200, "Fetch roles list details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch roles list details", []);
        }
    }

    public function get_all_roles() {
        try {
            $result = $this->service->getAllRoles();
            return ApiResponse::send(200, "Fetch roles details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch roles details", []);
        }
    }

    public function get_role_by_id($roleId) {
        try {
            $role_id = (int)$roleId;
            $result = $this->service->getRoleById($role_id);
            return ApiResponse::send(200, "Fetch role details successfully",["role"=>$result]);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch role details", []);
        }
    }

    public function update_role_by_id($roleId) {
        try {
            $role_id = (int)$roleId;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->updateById($role_id, $input);
            return ApiResponse::send(200, "Roles details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_role_by_id($roleId) {
        try {
            $role_id = (int)$roleId;
            $this->service->deleteRoleById($role_id);
            return ApiResponse::send(200, "Roles details delete successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch delet roles details", []);
        }
    }

    public function get_actions($roleId) {
        try {
            $role_id = (int)$roleId;
            $result = $this->service->getPolicy($role_id);
            return ApiResponse::send(200, "Fetch actions details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch actions details", []);
        }
    }
}
