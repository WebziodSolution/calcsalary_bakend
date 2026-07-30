<?php
namespace Common\Views;

use Common\Services\CompanyFunctionalityService;
use Common\Response\ApiResponse;
use Exception;

class CompanyFunctionalityController {
    private $service;

    public function __construct() {
        $this->service = new CompanyFunctionalityService();
    }

    public function get_all_functionality() {
        try {
            $result = $this->service->get_all_functionality();
            return ApiResponse::send(200, "Fetch functionality details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch functionality details", []);
        }
    }

    public function get_functionality($id) {
        try {
            $functionality_id = (int)$id;
            $result = $this->service->get_functionality($functionality_id);
            return ApiResponse::send(200, "Fetch functionality details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch functionality details", []);
        }
    }

    public function create_functionality() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_functionality($input);
            return ApiResponse::send(200, "Functionality details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update_functionality($id) {
        try {
            $functionality_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->update_functionality($functionality_id, $input);
            return ApiResponse::send(200, "Functionality details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_functionality($id) {
        try {
            $functionality_id = (int)$id;
            $this->service->delete_functionality($functionality_id);
            return ApiResponse::send(200, "Functionality details delete successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch delet functionality details", []);
        }
    }
}
