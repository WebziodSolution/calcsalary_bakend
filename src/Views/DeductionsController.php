<?php
namespace Common\Views;

use Common\Services\DeductionsService;
use Common\Response\ApiResponse;
use Exception;

class DeductionsController {
    private $service;

    public function __construct() {
        $this->service = new DeductionsService();
    }

    public function get_all_deductions() {
        try {
            $employee_id = isset($_GET['employeeId']) ? (int)$_GET['employeeId'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
            $result = $this->service->find_by_employee_id($employee_id);
            return ApiResponse::send(200, "Fetch deductions details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch deductions details", []);
        }
    }

    public function get_deductions($id) {
        try {
            $deduction_id = (int)$id;
            $result = $this->service->find_by_id($deduction_id);
            return ApiResponse::send(200, "Fetch deductions details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch deductions details", []);
        }
    }

    public function save_deductions() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                $input = [];
            }
            // Support single object wrap in array
            if (!empty($input) && !isset($input[0])) {
                $input = [$input];
            }
            $this->service->save_deductions($input);
            return ApiResponse::send(200, "Deductions details save successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to save deductions details", []);
        }
    }

    public function delete_deductions($id) {
        try {
            $deduction_id = (int)$id;
            $this->service->delete_by_id($deduction_id);
            return ApiResponse::send(200, "Deductions details delete successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch delet deductions details", []);
        }
    }
}
