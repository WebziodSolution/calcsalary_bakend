<?php
namespace Common\Views;

use Common\Services\ModuleService;
use Common\Response\ApiResponse;
use Exception;

class ModuleController {
    private $service;

    public function __construct() {
        $this->service = new ModuleService();
    }

    public function create_module() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->createModule($input);
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

            $result = $this->service->allModuleListPage($search_key, $page, $size);
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

            $result = $this->service->moduleByFunctionalityListPage($functionality_id, $search_key, $page, $size);
            return ApiResponse::send(200, "Fetch modules list details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch modules list details", []);
        }
    }

    public function get_all_modules() {
        try {
            $result = $this->service->getAllModules();
            return ApiResponse::send(200, "Fetch modules details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch modules details", []);
        }
    }

    public function get_module_by_id($moduleId) {
        try {
            $module_id = (int)$moduleId;
            $result = $this->service->getModuleById($module_id);
            return ApiResponse::send(200, "Fetch module details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch module details", []);
        }
    }

    public function update_module_by_id($moduleId) {
        try {
            $module_id = (int)$moduleId;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->updateModuleById($module_id, $input);
            return ApiResponse::send(200, "Module details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_module_by_id($moduleId) {
        try {
            $module_id = (int)$moduleId;
            $this->service->deleteModuleById($module_id);
            return ApiResponse::send(200, "Module details delete successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch delet module details", []);
        }
    }
}
