<?php
namespace Common\Views;

use Common\Services\HolidayTemplatesService;
use Common\Response\ApiResponse;
use Exception;

class HolidayTemplatesController {
    private $service;

    public function __construct() {
        $this->service = new HolidayTemplatesService();
    }

    public function get_all_holiday_templates_by_company_id($id) {
        try {
            $company_id = (int)$id;
            $result = $this->service->get_all_holiday_templates_by_company_id($company_id);
            return ApiResponse::send(200, "Fetch holiday template details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch holiday template details", []);
        }
    }

    public function get_holiday_template($id) {
        try {
            $template_id = (int)$id;
            $result = $this->service->get_holiday_template_by_id($template_id);
            return ApiResponse::send(200, "Fetch holiday template details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch holiday template details", []);
        }
    }

    public function create_holiday_template() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_holiday_template($input);
            return ApiResponse::send(201, "Holiday template added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update_holiday_template($id) {
        try {
            $template_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_holiday_template($template_id, $input);
            return ApiResponse::send(200, "Holiday template updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_holiday_template($id) {
        try {
            $template_id = (int)$id;
            $this->service->delete_holiday_template($template_id);
            return ApiResponse::send(200, "Holiday template deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete holiday template details", []);
        }
    }

    public function assign_employees() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $template_id = $input['holidayTemplateId'] ?? 0;
            $employee_ids = $input['employeeIds'] ?? [];
            $remove_employee_ids = $input['removeEmployeeIds'] ?? [];

            $result = $this->service->assign_employees($template_id, $employee_ids, $remove_employee_ids);
            return ApiResponse::send(200, "Template assigned successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }
}
