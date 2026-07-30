<?php
namespace Common\Views;

use Common\Services\CompanyDetailsService;
use Common\Response\ApiResponse;
use Common\Exception\GlobalException;
use Exception;

class CompanyDetailsController {
    private $service;

    public function __construct() {
        $this->service = new CompanyDetailsService();
    }

    public function search() {
        try {
            $name = $_GET['name'] ?? "";
            $active_str = $_GET['active'] ?? "";
            $active = ($active_str !== "") ? (int)$active_str : 2;

            $result = $this->service->search_companies($name, $active);
            return ApiResponse::send(200, "Fetch company details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch company details", []);
        }
    }

    public function get_all_company_details() {
        try {
            $active_str = $_GET['active'] ?? "";
            $active = ($active_str !== "") ? (int)$active_str : 2;

            $result = $this->service->get_all_company_details($active);
            return ApiResponse::send(200, "Fetch company details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch company details", []);
        }
    }

    public function get_company_details($id) {
        try {
            $company_id = (int)$id;
            $result = $this->service->get_company_details($company_id);
            return ApiResponse::send(200, "Fetch company details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch company details", []);
        }
    }

    public function create_company_details($step) {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_company_details($input, $step);
            return ApiResponse::send(201, "Company details added successfully", $result);
        } catch (GlobalException $e) {
            return ApiResponse::send(400, $e->getMessage(), []);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update_company_details($id, $step) {
        try {
            $company_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_company_details($company_id, $input, $step);
            return ApiResponse::send(200, "Company details updated successfully", $result);
        } catch (GlobalException $e) {
            return ApiResponse::send(400, $e->getMessage(), []);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_company_details($id) {
        try {
            $company_id = (int)$id;
            $this->service->delete_company_details($company_id);
            return ApiResponse::send(200, "Company deactivate successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch delet company details", []);
        }
    }

    public function upload_company_logo() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $company_id_val = $input['companyId'] ?? null;
            $logo_path = $input['companyLogo'] ?? null;

            if (!$company_id_val || !$logo_path) {
                return ApiResponse::send(400, "Missing companyId or companyLogo", []);
            }

            $company_id = (int)$company_id_val;
            $path = $this->service->upload_company_logo($company_id, $logo_path);
            if ($path === "Error") {
                return ApiResponse::send(404, "Image does not exist in the directory", "");
            }
            return ApiResponse::send(200, "Logo update successfully", $path);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update profile image", []);
        }
    }

    public function delete_company_logo($companyId) {
        try {
            $company_id = (int)$companyId;
            if ($this->service->delete_company_logo($company_id)) {
                return ApiResponse::send(200, "Logo deleted successfully", "");
            }
            return ApiResponse::send(500, "Logo not found", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete profile image", []);
        }
    }

    public function get_last_company() {
        try {
            $company_no = $this->service->get_last_company();
            if ($company_no) {
                return ApiResponse::send(200, "Fetch company details successfully", $company_no);
            }
            return ApiResponse::send(404, "No company details found", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch company details", []);
        }
    }

    public function update_auto_time_in_after_hours($companyId) {
        try {
            $company_id = (int)$companyId;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $time_str = $input['time'] ?? null;
            if (!$time_str) {
                return ApiResponse::send(400, "Missing time parameter", []);
            }

            $this->service->update_auto_time_in_after_hours($company_id, $time_str);
            return ApiResponse::send(200, "Company details updated successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update company details", []);
        }
    }

    public function get_auto_time_in_after_hours($companyId) {
        try {
            $company_id = (int)$companyId;
            $time_val = $this->service->get_auto_time_in_after_hours($company_id);
            return ApiResponse::send(200, "Company details fetched successfully", $time_val);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch company details", []);
        }
    }
}
