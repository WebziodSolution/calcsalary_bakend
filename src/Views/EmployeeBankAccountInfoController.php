<?php
namespace Common\Views;

use Common\Services\EmployeeBankAccountInfoService;
use Common\Response\ApiResponse;
use Exception;

class EmployeeBankAccountInfoController {
    private $service;

    public function __construct() {
        $this->service = new EmployeeBankAccountInfoService();
    }

    public function create_bank_account_info() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_bank_account_info($input);
            return ApiResponse::send(201, "Bank account info details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to add bank account info details", []);
        }
    }

    public function get_bank_account_info_by_id($id) {
        try {
            $bank_id = (int)$id;
            $result = $this->service->get_bank_account_info_by_id($bank_id);
            return ApiResponse::send(200, "Fetch bank account info details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch bank account info details", []);
        }
    }

    public function get_all_bank_account_info() {
        try {
            $result = $this->service->get_all_bank_account_info();
            return ApiResponse::send(200, "Fetch bank account info details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch bank account info details", []);
        }
    }

    public function update_bank_account_info($id) {
        try {
            $bank_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_bank_account_info($bank_id, $input);
            return ApiResponse::send(200, "Bank account info details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update bank account info details", []);
        }
    }

    public function delete_bank_account_info($id) {
        try {
            $bank_id = (int)$id;
            $this->service->delete_bank_account_info($bank_id);
            return ApiResponse::send(200, "Bank account info deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete bank account info details", []);
        }
    }

    public function upload_passbook_image() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            
            $company_id = isset($input['companyId']) ? (int)$input['companyId'] : 0;
            $id = isset($input['bankId']) ? (int)$input['bankId'] : (isset($input['id']) ? (int)$input['id'] : 0);
            $image_path = $input['bank'] ?? null;

            if (!$image_path) {
                return ApiResponse::send(400, "No file uploaded", []);
            }

            $result = $this->service->upload_passbook_image($company_id, $id, $image_path);
            if ($result === "Error") {
                return ApiResponse::send(500, "Fail to upload passbook image", []);
            }
            return ApiResponse::send(200, "Passbook image uploaded successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to upload passbook image", []);
        }
    }

    public function delete_passbook_image($companyId, $bankId) {
        try {
            $company_id = (int)$companyId;
            $bank_id = (int)$bankId;
            $result = $this->service->delete_passbook_image($company_id, $bank_id);
            if ($result) {
                return ApiResponse::send(200, "Passbook image deleted successfully", "");
            } else {
                return ApiResponse::send(500, "Fail to delete passbook image", []);
            }
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete passbook image", []);
        }
    }
}
