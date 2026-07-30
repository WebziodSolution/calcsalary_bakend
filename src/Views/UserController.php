<?php
namespace Common\Views;

use Common\Services\UserService;
use Common\Response\ApiResponse;
use Common\Auth\JwtTokenUtil;
use Common\Exception\GlobalException;
use Exception;

class UserController {
    private $service;
    private $jwt_util;

    public function __construct() {
        $this->service = new UserService();
        $this->jwt_util = new JwtTokenUtil();
    }

    public function get_all_users() {
        try {
            $company_id = isset($_GET['companyId']) ? (int)$_GET['companyId'] : null;
            $dept_ids_str = $_GET['departmentIds'] ?? null;
            $emp_ids_str = $_GET['employeeIds'] ?? null;

            $dept_ids = null;
            if ($dept_ids_str) {
                $dept_ids = array_filter(array_map('intval', explode(',', $dept_ids_str)));
            }

            $emp_ids = null;
            if ($emp_ids_str) {
                $emp_ids = array_filter(array_map('intval', explode(',', $emp_ids_str)));
            }

            $result = $this->service->get_all_users($company_id, $dept_ids, $emp_ids);
            return ApiResponse::send(200, "User fetched successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function get_user($id) {
        try {
            $user_id = (int)$id;
            $result = $this->service->get_user_by_id($user_id);
            return ApiResponse::send(200, "User fetched successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function create_user() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $result = $this->service->create_user($input);
            return ApiResponse::send(201, "User added successfully", $result);
        } catch (GlobalException $e) {
            return ApiResponse::send(400, $e->getMessage(), []);
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function update_user($id) {
        try {
            $user_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $this->service->update_user($user_id, $input);
            return ApiResponse::send(200, "User updated successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function delete_user($id) {
        try {
            $user_id = (int)$id;
            $this->service->delete_user($user_id);
            return ApiResponse::send(200, "User deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, $e->getMessage(), []);
        }
    }

    public function user_login() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $res_body = $this->service->user_login($input);
            if (isset($res_body['error'])) {
                return ApiResponse::send(400, $res_body['error'], $res_body);
            }
            if (empty($res_body)) {
                return ApiResponse::send(400, "Invalid credentials", $res_body);
            }
            return ApiResponse::send(200, "Login successful", $res_body);
        } catch (Exception $e) {
            return ApiResponse::send(500, "failed to login", []);
        }
    }

    public function generate_reset_link() {
        try {
            $email = $_GET['email'] ?? null;
            $user_name = $_GET['userName'] ?? null;
            $company_id = $_GET['companyId'] ?? null;

            if (!$email || !$user_name || !$company_id) {
                return ApiResponse::send(400, "Missing required parameters: email, userName, companyId", []);
            }

            if ($this->service->generate_reset_link($email, $user_name, $company_id)) {
                return ApiResponse::send(200, "A password reset link has been sent to " . $email, "");
            } else {
                return ApiResponse::send(400, "This email or username is not registered", "");
            }
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to generate link", []);
        }
    }

    public function validate_token() {
        try {
            $token = $_GET['token'] ?? null;
            if (!$token) {
                return ApiResponse::send(400, "Missing token parameter", []);
            }

            $validate_res = $this->service->validate_token($token);
            if ($validate_res !== null) {
                return ApiResponse::send(200, $validate_res['message'] ?? "Token is valid", $validate_res);
            }
            return ApiResponse::send(400, "Invalid token", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch account details", []);
        }
    }

    public function reset_password() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $response = $this->service->reset_password($input);
            if (isset($response['success'])) {
                return ApiResponse::send(200, "Pin reset successfully", $response);
            }
            return ApiResponse::send(400, "Failed to reset pin", $response);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Error resetting pin", ["error" => $e->getMessage()]);
        }
    }

    private function get_token_from_header() {
        $headers = getallheaders();
        $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);
        if (!$auth_header && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        if ($auth_header && preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            return $matches[1];
        }
        return "";
    }

    public function upload_profile_image() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $user_id_val = $input['userId'] ?? null;
            
            if ($user_id_val !== null) {
                $user_id = (int)$user_id_val;
            } else {
                $token = $this->get_token_from_header();
                $user_id = (int)$this->jwt_util->extract_user_id($token);
            }

            $profile_img = $input['profileImage'] ?? null;
            if (!$profile_img) {
                return ApiResponse::send(400, "profileImage parameter is required", "");
            }

            $path = $this->service->upload_profile_image($user_id, $profile_img);
            if ($path === "Error") {
                return ApiResponse::send(404, "Image does not exist in the directory", "");
            }
            return ApiResponse::send(200, "Profile image update successfully", $path);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to update profile image", []);
        }
    }

    public function delete_profile_image() {
        try {
            $token = $this->get_token_from_header();
            $user_id = (int)$this->jwt_util->extract_user_id($token);

            if ($this->service->delete_profile_image($user_id)) {
                return ApiResponse::send(200, "Profile image deleted successfully", "");
            }
            return ApiResponse::send(500, "Profile image not found", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete profile image", []);
        }
    }
}
