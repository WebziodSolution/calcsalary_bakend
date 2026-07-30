<?php
namespace Common\Views;

use Common\Services\CompanyEmployeeService;
use Common\Response\ApiResponse;
use Exception;

class CompanyEmployeeController {
    private $service;

    public function __construct() {
        $this->service = new CompanyEmployeeService();
    }

    public function get_all_employee_by_company_id($companyId) {
        try {
            $company_id = (int)$companyId;
            $result = $this->service->get_all_employee_by_company_id($company_id);
            return ApiResponse::send(200, "Fetch employee details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch company details", []);
        }
    }

    public function get_employee_pf_and_pt_report() {
        try {
            $company_id = (int)($_GET['companyId'] ?? 0);
            $type_str = $_GET['type'] ?? "";
            $month = $_GET['month'] ?? "";
            $user_time_zone = $_GET['userTimeZone'] ?? "";

            $result = $this->service->get_reports($company_id, $type_str, $month, $user_time_zone);
            return ApiResponse::send(200, "Fetch employee details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch company details", []);
        }
    }

    public function get_all_employee_list_by_company_id($companyId) {
        try {
            $company_id = (int)$companyId;
            $result = $this->service->get_all_employee_list_by_company_id($company_id);
            return ApiResponse::send(200, "Fetch employee details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch company details", []);
        }
    }

    public function get_employee($id) {
        try {
            $emp_id = (int)$id;
            $result = $this->service->get_employee($emp_id);
            return ApiResponse::send(200, "Fetch employee details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch company details", []);
        }
    }

    public function create_employee() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_employee($input);
            return ApiResponse::send(200, "Employee added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update_employee($id) {
        try {
            $emp_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_employee($emp_id, $input);
            return ApiResponse::send(200, "Employee updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_employee($id) {
        try {
            $emp_id = (int)$id;
            $this->service->delete_employee($emp_id);
            return ApiResponse::send(200, "Employee deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch delet company details", []);
        }
    }

    public function upload_employee_profile() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $company_id = isset($input['companyId']) ? (int)$input['companyId'] : null;
            $employee_id = isset($input['employeeId']) ? (int)$input['employeeId'] : null;
            $profile_image = $input['profileImage'] ?? ($input['employee'] ?? null);

            if (!$company_id || !$employee_id || !$profile_image) {
                return ApiResponse::send(400, "Missing parameters", []);
            }

            $path = $this->service->upload_employee_profile($company_id, $employee_id, $profile_image);
            if ($path === "Error") {
                return ApiResponse::send(404, "Image does not exist in the directory", "");
            }
            return ApiResponse::send(200, "Profile image updated successfully", $path);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update profile image", []);
        }
    }

    public function delete_employee_image($companyId, $employeeId) {
        try {
            $comp_id = (int)$companyId;
            $emp_id = (int)$employeeId;
            if ($this->service->delete_employee_profile($comp_id, $emp_id)) {
                return ApiResponse::send(200, "Profile image deleted successfully", "");
            }
            return ApiResponse::send(500, "Image not found", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete profile image", []);
        }
    }

    public function upload_employee_aadhar_image() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $company_id = isset($input['companyId']) ? (int)$input['companyId'] : null;
            $employee_id = isset($input['employeeId']) ? (int)$input['employeeId'] : null;
            $aadhar_image = $input['aadharImage'] ?? ($input['employee'] ?? null);

            if (!$company_id || !$employee_id || !$aadhar_image) {
                return ApiResponse::send(400, "Missing parameters", []);
            }

            $path = $this->service->upload_employee_aadhar_image($company_id, $employee_id, $aadhar_image);
            if ($path === "Error") {
                return ApiResponse::send(404, "Image does not exist in the directory", "");
            }
            return ApiResponse::send(200, "Aadhar image updated successfully", $path);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update profile image", []);
        }
    }

    public function delete_employee_aadhar_image($companyId, $employeeId) {
        try {
            $comp_id = (int)$companyId;
            $emp_id = (int)$employeeId;
            if ($this->service->delete_employee_aadhar_image($comp_id, $emp_id)) {
                return ApiResponse::send(200, "Aadhar image deleted successfully", "");
            }
            return ApiResponse::send(500, "Aadhar image not found", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete profile image", []);
        }
    }

    public function create_employee_from_tsp() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_employee_from_tsp($input);
            return ApiResponse::send(200, "Employee details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update_employee_from_tsp($id) {
        try {
            $emp_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_employee_from_tsp($emp_id, $input);
            return ApiResponse::send(200, "Employee details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function get_last_user_id() {
        try {
            $result = $this->service->get_last_user_id();
            return ApiResponse::send(200, "Fetch employee details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch company details", []);
        }
    }
}
