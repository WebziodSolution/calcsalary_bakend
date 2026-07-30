<?php
namespace Common\Views;

use Common\Services\UserShiftService;
use Common\Response\ApiResponse;
use Exception;

class UserShiftController {
    private $service;

    public function __construct() {
        $this->service = new UserShiftService();
    }

    public function get_all_shift() {
        try {
            $result = $this->service->getAllUserShift();
            return ApiResponse::send(200, "Fetch shift details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch shift details", []);
        }
    }

    public function get_user_shift($id) {
        try {
            $id_val = (int)$id;
            $result = $this->service->getUserShift($id_val);
            return ApiResponse::send(200, "Fetch shift details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch shift details", []);
        }
    }

    public function create_user_shift() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->createUserShift($input);
            return ApiResponse::send(200, "Shift details added successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update_user_shift($id) {
        try {
            $id_val = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->updateUserShift($id_val, $input);
            return ApiResponse::send(200, "Shift details updated successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_user_shift($id) {
        try {
            $id_val = (int)$id;
            $this->service->deleteUserShift($id_val);
            return ApiResponse::send(200, "Shift details delete successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch delet shift details", []);
        }
    }
}
