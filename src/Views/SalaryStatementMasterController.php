<?php
namespace Common\Views;

use Common\Services\SalaryStatementMasterService;
use Common\Response\ApiResponse;
use Exception;

class SalaryStatementMasterController {
    private $service;

    public function __construct() {
        $this->service = new SalaryStatementMasterService();
    }

    public function get_all_statement_masters($id) {
        try {
            $company_id = (int)$id;
            $result = $this->service->getAllSalaryStatementMasters($company_id);
            return ApiResponse::send(200, "Fetch salary statement successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch salary statement", []);
        }
    }

    public function get_statement_masters_by_month_and_year() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            
            $company_id_str = $input['companyId'] ?? null;
            $month_str = $input['month'] ?? null;
            $year_str = $input['year'] ?? null;

            $company_id = $company_id_str !== null ? (int)$company_id_str : null;
            $month = $month_str !== null ? (int)$month_str : null;
            $year = $year_str !== null ? (int)$year_str : null;

            $result = $this->service->getSalaryStatementMastersByMonthAndYear($company_id, $month, $year);
            return ApiResponse::send(200, "Fetch salary statement successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch salary statement", []);
        }
    }

    public function get_salary_statement_master_by_id($id) {
        try {
            $master_id = (int)$id;
            $result = $this->service->getSalaryStatementMasterById($master_id);
            return ApiResponse::send(200, "Fetch salary statement successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch salary statement", []);
        }
    }

    public function create_salary_statement_master() {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $result = $this->service->createSalaryStatementMaster($input);
            return ApiResponse::send(201, "Salary statement added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to add salary statement", []);
        }
    }

    public function update_salary_statement_master($id) {
        try {
            $master_id = (int)$id;
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $result = $this->service->updateSalaryStatementMaster($master_id, $input);
            return ApiResponse::send(200, "Salary statement updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to updated salary statement", []);
        }
    }

    public function delete_salary_statement_master($id) {
        try {
            $master_id = (int)$id;
            $this->service->deleteSalaryStatementMaster($master_id);
            return ApiResponse::send(200, "Salary statement deleted successfully", []);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete salary statement", []);
        }
    }
}
