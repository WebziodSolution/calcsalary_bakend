<?php
namespace Common\Views;

use Common\Services\ContractorService;
use Common\Response\ApiResponse;
use Exception;

class ContractorController {
    private $service;

    public function __construct() {
        $this->service = new ContractorService();
    }

    public function get_all_contractors() {
        try {
            $result = $this->service->get_all_contractors();
            return ApiResponse::send(200, "Fetch contractor details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch contractor details", []);
        }
    }

    public function get_contractor($id) {
        try {
            $contractor_id = (int)$id;
            $result = $this->service->get_contractor($contractor_id);
            return ApiResponse::send(200, "Fetch contractor details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch contractor details", []);
        }
    }

    public function create_contractor() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $this->service->create_contractor($input);
            return ApiResponse::send(201, "Contractor details added successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to create contractor details", []);
        }
    }

    public function update_contractor($id) {
        try {
            $contractor_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $this->service->update_contractor($contractor_id, $input);
            return ApiResponse::send(200, "Contractor details updated successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update contractor details", []);
        }
    }

    public function delete_contractor($id) {
        try {
            $contractor_id = (int)$id;
            $this->service->delete_contractor($contractor_id);
            return ApiResponse::send(200, "Contractor deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete contractor details", []);
        }
    }
}
