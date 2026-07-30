<?php
namespace Common\Views;

use Common\Services\CompanyThemeService;
use Common\Response\ApiResponse;
use Exception;

class CompanyThemeController {
    private $service;

    public function __construct() {
        $this->service = new CompanyThemeService();
    }

    public function get_all_company_theme($id) {
        try {
            $company_id = (int)$id;
            $result = $this->service->get_all_theme($company_id);
            return ApiResponse::send(200, "Fetch theme details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch theme details", []);
        }
    }

    public function get_company_theme($id) {
        try {
            $theme_id = (int)$id;
            $result = $this->service->get_theme($theme_id);
            return ApiResponse::send(200, "Fetch theme details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch theme details", []);
        }
    }

    public function create_company_theme() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_theme($input);
            return ApiResponse::send(200, "Theme details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update_company_theme($id) {
        try {
            $theme_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_theme($theme_id, $input);
            return ApiResponse::send(200, "Theme details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_company_theme($id) {
        try {
            $theme_id = (int)$id;
            $this->service->delete_theme($theme_id);
            return ApiResponse::send(200, "Theme details delete successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch delet theme details", []);
        }
    }
}
