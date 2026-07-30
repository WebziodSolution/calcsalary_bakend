<?php
namespace Common\Views;

use Common\Services\WeeklyOffService;
use Common\Response\ApiResponse;
use Exception;

class WeeklyOffController {
    private $service;

    public function __construct() {
        $this->service = new WeeklyOffService();
    }

    public function get_all_by_company($id) {
        try {
            $company_id = (int)$id;
            $result = $this->service->getAllByCompany($company_id);
            return ApiResponse::send(200, "Template fetched successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function get_by_id($id) {
        try {
            $week_off_id = (int)$id;
            $result = $this->service->getById($week_off_id);
            return ApiResponse::send(200, "Template fetched successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function assign_employees() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $week_off_id = $input['weekOffId'] ?? 0;
            $employee_ids = $input['employeeIds'] ?? [];
            $remove_employee_ids = $input['removeEmployeeIds'] ?? [];

            $result = $this->service->assignEmployees($employee_ids, $week_off_id, $remove_employee_ids);
            return ApiResponse::send(200, "Template assigned successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function create() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create($input);
            return ApiResponse::send(201, "Template created successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update($id) {
        try {
            $week_off_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update($week_off_id, $input);
            return ApiResponse::send(200, "Template updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete($id) {
        try {
            $week_off_id = (int)$id;
            $this->service->delete($week_off_id);
            return ApiResponse::send(200, "Template deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function assign_default_template($id) {
        try {
            $week_off_id = (int)$id;
            $this->service->assignDefaultWeeklyOff($week_off_id);
            return ApiResponse::send(200, "This template is set as default.", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }
}
