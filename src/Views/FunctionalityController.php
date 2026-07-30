<?php
namespace Common\Views;

use Common\Services\FunctionalityService;
use Common\Response\ApiResponse;
use Exception;

class FunctionalityController {
    private $service;

    public function __construct() {
        $this->service = new FunctionalityService();
    }

    public function get_all_functionality() {
        try {
            $result = $this->service->getAllFunctionality();
            return ApiResponse::send(200, "Fetch functionality details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch functionality details", []);
        }
    }

    public function get_functionality($id) {
        try {
            $id_val = (int)$id;
            $result = $this->service->getFunctionality($id_val);
            return ApiResponse::send(200, "Fetch functionality details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch functionality details", []);
        }
    }

    public function create_functionality() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->createFunctionality($input);
            return ApiResponse::send(200, "Functionality details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update_functionality($id) {
        try {
            $id_val = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->updateFunctionality($id_val, $input);
            return ApiResponse::send(200, "Functionality details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_functionality($id) {
        try {
            $id_val = (int)$id;
            $this->service->deleteFunctionality($id_val);
            return ApiResponse::send(200, "Functionality details delete successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch delet functionality details", []);
        }
    }
}
