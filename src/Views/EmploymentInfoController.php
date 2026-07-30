<?php
namespace Common\Views;

use Common\Services\EmploymentInfoService;
use Common\Response\ApiResponse;
use Exception;

class EmploymentInfoController {
    private $service;

    public function __construct() {
        $this->service = new EmploymentInfoService();
    }

    public function get_all_employment_info() {
        try {
            $result = $this->service->get_all_employment_info();
            return ApiResponse::send(200, "Fetch employmentInfo details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch employmentInfo details", []);
        }
    }

    public function get_employment_info_by_id($id) {
        try {
            $info_id = (int)$id;
            $result = $this->service->get_employment_info_by_id($info_id);
            return ApiResponse::send(200, "Fetch employmentInfo details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch employmentInfo details", []);
        }
    }

    public function create_employment_info() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_employment_info($input);
            return ApiResponse::send(201, "EmploymentInfo details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to add employmentInfo details", []);
        }
    }

    public function update_employment_info($id) {
        try {
            $info_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_employment_info($info_id, $input);
            return ApiResponse::send(200, "EmploymentInfo details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update employmentInfo details", []);
        }
    }

    public function delete_employment_info($id) {
        try {
            $info_id = (int)$id;
            $this->service->delete_employment_info($info_id);
            return ApiResponse::send(200, "EmploymentInfo details deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete employmentInfo details", []);
        }
    }
}
