<?php
namespace Common\Views;

use Common\Services\HolidayTemplateDetailsService;
use Common\Response\ApiResponse;
use Exception;

class HolidayTemplateDetailsController {
    private $service;

    public function __construct() {
        $this->service = new HolidayTemplateDetailsService();
    }

    public function get_all_holiday_template_details_by_template_id($id) {
        try {
            $template_id = (int)$id;
            $result = $this->service->get_all_holiday_template_details_by_template_id($template_id);
            return ApiResponse::send(200, "Fetch holiday details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch holiday details", []);
        }
    }

    public function get_holiday_template_details($id) {
        try {
            $details_id = (int)$id;
            $result = $this->service->get_holiday_template_details_by_id($details_id);
            return ApiResponse::send(200, "Fetch holiday details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch holiday details", []);
        }
    }

    public function create_holiday_template_details() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_holiday_template_details($input);
            return ApiResponse::send(201, "Holiday details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to add holiday details", []);
        }
    }

    public function update_holiday_template_details($id) {
        try {
            $details_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_holiday_template_details($details_id, $input);
            return ApiResponse::send(200, "Holiday details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update holiday details", []);
        }
    }

    public function delete_holiday_template_details($id) {
        try {
            $details_id = (int)$id;
            $this->service->delete_holiday_template_details($details_id);
            return ApiResponse::send(200, "Holiday details deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete holiday details", []);
        }
    }
}
