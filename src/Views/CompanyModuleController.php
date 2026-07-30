<?php
namespace Common\Views;

use Common\Services\CompanyModuleService;
use Common\Response\ApiResponse;
use Exception;

class CompanyModuleController {
    private $service;

    public function __construct() {
        $this->service = new CompanyModuleService();
    }

    public function create_module() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_module($input);
            return ApiResponse::send(200, "Module details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function all_module_list_page() {
        try {
            $search_key = $_GET['searchKey'] ?? "";
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
            $size = isset($_GET['size']) ? (int)$_GET['size'] : 10;

            $result = $this->service->all_module_list_page($search_key, $page, $size);
            return ApiResponse::send(200, "Fetch modules list details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch modules list details", []);
        }
    }

    public function module_by_functionality_list_page() {
        try {
            $functionality_id = isset($_GET['functionalityId']) ? (int)$_GET['functionalityId'] : 0;
            $search_key = $_GET['searchKey'] ?? "";
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
            $size = isset($_GET['size']) ? (int)$_GET['size'] : 10;

            $result = $this->service->module_by_functionality_list_page($functionality_id, $search_key, $page, $size);
            return ApiResponse::send(200, "Fetch modules list details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch modules list details", []);
        }
    }

    public function get_all_modules() {
        try {
            $result = $this->service->get_all_modules();
            return ApiResponse::send(200, "Fetch modules details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch modules details", []);
        }
    }

    public function get_module_by_id($moduleId) {
        try {
            $module_id = (int)$moduleId;
            $result = $this->service->get_module_by_id($module_id);
            return ApiResponse::send(200, "Fetch module details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch module details", []);
        }
    }

    public function update_module_by_id($moduleId) {
        try {
            $module_id = (int)$moduleId;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_module_by_id($module_id, $input);
            return ApiResponse::send(200, "Module details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_module_by_id($moduleId) {
        try {
            $module_id = (int)$moduleId;
            $this->service->delete_module_by_id($module_id);
            return ApiResponse::send(200, "Module details delete successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch delet module details", []);
        }
    }
}
