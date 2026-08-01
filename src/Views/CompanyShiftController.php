<?php
namespace Common\Views;

use Common\Services\CompanyShiftService;
use Common\Response\ApiResponse;
use Exception;

class CompanyShiftController {
    private $service;

    public function __construct() {
        $this->service = new CompanyShiftService();
    }

    public function get_all_shifts($companyId) {
        try {
            $company_id = (int)$companyId;
            $result = $this->service->get_all_shifts($company_id);
            return ApiResponse::send(200, "Fetch shift details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch shift details", []);
        }
    }

    public function get_shift_by_id($id) {
        try {
            $shift_id = (int)$id;
            $result = $this->service->get_shift_by_id($shift_id);
            return ApiResponse::send(200, "Fetch shift details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch shift details", []);
        }
    }

    public function create_shift() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_shift($input);
            return ApiResponse::send(201, "Shift details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update_shift($id) {
        try {
            $shift_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_shift($shift_id, $input);
            return ApiResponse::send(200, "Shift details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_shift($id) {
        try {
            $shift_id = (int)$id;
            $this->service->delete_shift($shift_id);
            return ApiResponse::send(200, "Shift details delete successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch delet shift details", []);
        }
    }
}
