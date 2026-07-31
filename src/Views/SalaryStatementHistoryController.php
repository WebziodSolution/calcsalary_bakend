<?php
namespace Common\Views;

use Common\Services\SalaryStatementHistoryService;
use Common\Response\ApiResponse;
use Exception;

class SalaryStatementHistoryController {
    private $service;

    public function __construct() {
        $this->service = new SalaryStatementHistoryService();
    }

    public function filter_salary_statement_history() {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            
            $employee_ids = $input['employeeIds'] ?? [];
            $department_ids = $input['departmentIds'] ?? [];
            $months = $input['month'] ?? [];
            $company_id = isset($input['companyId']) ? (int)$input['companyId'] : null;

            $result = $this->service->filterSalaryStatementHistory($employee_ids, $department_ids, $months, $company_id);
            return ApiResponse::send(200, "Fetch salary data successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch salary data", []);
        }
    }

    public function get_salary_statement_history($id) {
        try {
            $history_id = (int)$id;
            $result = $this->service->getSalaryStatementHistory($history_id);
            return ApiResponse::send(200, "Fetch salary data successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch salary data", []);
        }
    }

    public function create() {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $result = $this->service->addSalaryStatement($input);
            return ApiResponse::send(201, "Salary data added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update($id) {
        try {
            $history_id = (int)$id;
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $result = $this->service->updateSalaryStatement($history_id, $input);
            return ApiResponse::send(200, "Salary data updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update salary data", []);
        }
    }

    public function delete($id) {
        try {
            $history_id = (int)$id;
            $this->service->deleteSalaryStatement($history_id);
            return ApiResponse::send(200, "Salary data deleted successfully", []);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete salary data", []);
        }
    }
}
