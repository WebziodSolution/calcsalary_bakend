<?php
namespace Common\Views;

use Common\Services\EmployeeSalaryStatementService;
use Common\Response\ApiResponse;
use Exception;

class EmployeeSalaryStatementController {
    private $service;

    public function __construct() {
        $this->service = new EmployeeSalaryStatementService();
    }

    public function get_employee_salary_statements() {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($input['companyId'])) {
                return ApiResponse::send(400, "Invalid request body", ["companyId" => "Company ID is required"]);
            }
            $result = $this->service->get_employee_salary_statements($input);
            return ApiResponse::send(200, "Fetch statement successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch statement", null);
        }
    }
}
