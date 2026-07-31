<?php
namespace Common\Services;

use Common\Models\Users;
use Common\Models\CompanyEmployee;
use Common\Models\CompanyDetails;
use Common\Models\CompanyTheme;
use Common\Models\Department;
use Common\Models\Roles;
use Common\Models\Contractor;
use Common\Models\Locations;
use Common\Models\CompanyEmployeeRoles;
use Common\Auth\JwtTokenUtil;
use Common\Exception\GlobalException;
use Exception;

class UserService {
    private $common_service;
    private $jwt_util;

    public function __construct() {
        $this->common_service = new CommonService();
        $this->jwt_util = new JwtTokenUtil();
    }

    private function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode($data) {
        $b64 = str_replace(['-', '_'], ['+', '/'], $data);
        $padded = str_pad($b64, strlen($b64) + (4 - strlen($b64) % 4) % 4, '=', STR_PAD_RIGHT);
        return base64_decode($padded);
    }

    private function check_django_password($password, $django_hash) {
        if (strpos($django_hash, 'pbkdf2_sha256$') === 0) {
            $parts = explode('$', $django_hash);
            if (count($parts) === 4) {
                $iterations = (int)$parts[1];
                $salt = $parts[2];
                $hash = $parts[3];
                
                $calc_hash_raw = hash_pbkdf2('sha256', $password, $salt, $iterations, 0, true);
                $calc_hash_b64 = base64_encode($calc_hash_raw);
                return hash_equals($hash, $calc_hash_b64);
            }
        }
        return false;
    }

    public function get_all_users($company_id = null, $department_ids = null, $employee_ids = null) {
        try {
            if ($company_id !== null || !empty($department_ids) || !empty($employee_ids)) {
                $where_clauses = [];
                $params = [];
                if ($company_id !== null) {
                    $where_clauses[] = "company_id = :company_id";
                    $params["company_id"] = $company_id;
                }
                if (!empty($department_ids)) {
                    $placeholders = [];
                    foreach ($department_ids as $idx => $id) {
                        $key = "dept_id_" . $idx;
                        $placeholders[] = ":" . $key;
                        $params[$key] = $id;
                    }
                    $where_clauses[] = "department_id IN (" . implode(", ", $placeholders) . ")";
                }
                if (!empty($employee_ids)) {
                    $placeholders = [];
                    foreach ($employee_ids as $idx => $id) {
                        $key = "emp_id_" . $idx;
                        $placeholders[] = ":" . $key;
                        $params[$key] = $id;
                    }
                    $where_clauses[] = "employee_id IN (" . implode(", ", $placeholders) . ")";
                }

                $where = implode(" AND ", $where_clauses);
                $employees = DbHelper::findAll(CompanyEmployee::class, $where, $params, "id ASC");
                
                $employee_service = new CompanyEmployeeService();
                $dtos = [];
                foreach ($employees as $emp) {
                    $dtos[] = $employee_service->get_employee($emp->employeeId);
                }
                return $dtos;
            } else {
                $users_list = DbHelper::findAll(Users::class, "1=1", [], "user_Id ASC");
                $dtos = [];
                foreach ($users_list as $user) {
                    $dtos[] = $this->get_user_by_id($user->userId);
                }
                return $dtos;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_user_by_id($user_id) {
        try {
            $user = DbHelper::findById(Users::class, $user_id);
            if (!$user) {
                throw new Exception("User not found");
            }
            
            $role_name = null;
            if ($user->role) {
                $role = DbHelper::findById(Roles::class, $user->role);
                $role_name = $role ? $role->roleName : null;
            }

            return [
                "userId" => $user->userId,
                "firstName" => $user->firstName,
                "lastName" => $user->lastName,
                "middleName" => $user->middleName,
                "email" => $user->email,
                "phone" => $user->phone,
                "password" => $user->password,
                "hourlyRate" => $user->hourlyRate,
                "gender" => $user->gender,
                "personalIdentificationNumber" => $user->personalIdentificationNumber,
                "address1" => $user->address1,
                "address2" => $user->address2,
                "city" => $user->city,
                "zipCode" => $user->zipCode,
                "country" => $user->country,
                "state" => $user->state,
                "birthDate" => $user->birthDate instanceof \DateTimeInterface ? $user->birthDate->format("Y-m-d") : ($user->birthDate ? date("Y-m-d", strtotime($user->birthDate)) : null),
                "emergencyContact" => $user->emergencyContact,
                "contactPhone" => $user->contactPhone,
                "relationship" => $user->relationship,
                "departmentId" => $user->department,
                "roleId" => $user->role,
                "userShiftId" => $user->userShift,
                "contractorId" => $user->contractor,
                "roleName" => $role_name,
                "profileImage" => $user->profileImage,
                "userName" => $user->userName,
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_user($user_dto) {
        try {
            $dept_id = $user_dto['departmentId'] ?? null;
            $role_id = $user_dto['roleId'] ?? null;

            $department = DbHelper::findById(Department::class, $dept_id);
            if (!$department) {
                throw new Exception("Department not found");
            }

            $role = DbHelper::findById(Roles::class, $role_id);
            if (!$role) {
                throw new Exception("Role not found");
            }

            $user_name = $user_dto['userName'] ?? null;
            if ($user_name) {
                $user_has = DbHelper::findFirst(Users::class, "user_name = :username", ["username" => $user_name]);
                if ($user_has) {
                    throw new GlobalException("Username is already taken.");
                }
            }

            $pin = $user_dto['personalIdentificationNumber'] ?? null;
            if ($pin) {
                $user_has = DbHelper::findFirst(Users::class, "personal_identification_number = :pin", ["pin" => $pin]);
                if ($user_has) {
                    throw new GlobalException("Personal identification number must be unique");
                }
            }

            $user = new Users();
            $user->department = $dept_id;
            $user->role = $role_id;
            $user->firstName = $user_dto['firstName'] ?? null;
            $user->lastName = $user_dto['lastName'] ?? null;
            $user->middleName = $user_dto['middleName'] ?? null;
            $user->email = $user_dto['email'] ?? null;
            $user->userName = $user_name;
            $user->phone = $user_dto['phone'] ?? null;
            $user->password = $user_dto['password'] ?? null;
            $user->gender = $user_dto['gender'] ?? null;
            $user->hourlyRate = $user_dto['hourlyRate'] ?? null;
            $user->personalIdentificationNumber = $pin;
            $user->address1 = $user_dto['address1'] ?? null;
            $user->address2 = $user_dto['address2'] ?? null;
            $user->city = $user_dto['city'] ?? null;
            $user->zipCode = $user_dto['zipCode'] ?? null;
            $user->country = $user_dto['country'] ?? null;
            $user->state = $user_dto['state'] ?? null;
            if (!empty($user_dto['birthDate'])) {
                $user->birthDate = $this->common_service->convert_string_to_date($user_dto['birthDate']);
            }
            $user->emergencyContact = $user_dto['emergencyContact'] ?? null;
            $user->contactPhone = $user_dto['contactPhone'] ?? null;
            $user->relationship = $user_dto['relationship'] ?? null;
            $user->profileImage = "";

            $contractor_id = $user_dto['contractorId'] ?? null;
            if ($contractor_id) {
                $contractor = DbHelper::findById(Contractor::class, $contractor_id);
                if (!$contractor) {
                    throw new Exception("Contractor not found");
                }
                $user->contractor = $contractor_id;
            }

            $user = DbHelper::insert($user);
            return $this->get_user_by_id($user->userId);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_user($user_id, $user_dto) {
        try {
            $user = DbHelper::findById(Users::class, $user_id);
            if (!$user) {
                throw new Exception("User not found");
            }

            $dept_id = $user_dto['departmentId'] ?? null;
            $role_id = $user_dto['roleId'] ?? null;

            $department = DbHelper::findById(Department::class, $dept_id);
            if (!$department) {
                throw new Exception("Department not found");
            }

            $role = DbHelper::findById(Roles::class, $role_id);
            if (!$role) {
                throw new Exception("Role not found");
            }

            $user_name = $user_dto['userName'] ?? null;
            if ($user_name) {
                $user_has = DbHelper::findFirst(Users::class, "user_name = :username AND user_Id != :userid", [
                    "username" => $user_name,
                    "userid" => $user_id
                ]);
                if ($user_has) {
                    throw new GlobalException("Username is already taken.");
                }
            }

            $user->department = $dept_id;
            $user->role = $role_id;
            $user->firstName = $user_dto['firstName'] ?? null;
            $user->lastName = $user_dto['lastName'] ?? null;
            $user->middleName = $user_dto['middleName'] ?? null;
            $user->email = $user_dto['email'] ?? null;
            $user->userName = $user_name;
            $user->phone = $user_dto['phone'] ?? null;
            $user->password = $user_dto['password'] ?? null;
            $user->gender = $user_dto['gender'] ?? null;
            $user->hourlyRate = $user_dto['hourlyRate'] ?? null;
            $user->personalIdentificationNumber = $user_dto['personalIdentificationNumber'] ?? null;
            $user->address1 = $user_dto['address1'] ?? null;
            $user->address2 = $user_dto['address2'] ?? null;
            $user->city = $user_dto['city'] ?? null;
            $user->zipCode = $user_dto['zipCode'] ?? null;
            $user->country = $user_dto['country'] ?? null;
            $user->state = $user_dto['state'] ?? null;
            if (!empty($user_dto['birthDate'])) {
                $user->birthDate = $this->common_service->convert_string_to_date($user_dto['birthDate']);
            } else {
                $user->birthDate = null;
            }
            $user->emergencyContact = $user_dto['emergencyContact'] ?? null;
            $user->contactPhone = $user_dto['contactPhone'] ?? null;
            $user->relationship = $user_dto['relationship'] ?? null;

            $contractor_id = $user_dto['contractorId'] ?? null;
            if ($contractor_id) {
                $contractor = DbHelper::findById(Contractor::class, $contractor_id);
                if (!$contractor) {
                    throw new Exception("Contractor not found");
                }
                $user->contractor = $contractor_id;
            } else {
                $user->contractor = null;
            }

            DbHelper::update($user);
            return $this->get_user_by_id($user->userId);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_user($user_id) {
        try {
            $user = DbHelper::findById(Users::class, $user_id);
            if (!$user) {
                throw new Exception("User not found");
            }
            DbHelper::delete(Users::class, $user_id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function user_login($login_dto) {
        $res_body = [];
        try {
            $config = require __DIR__ . '/../../config/settings.php';
            $config_company_id = $config['companyId'] ?? '108108';
            $req_company_id = strval($login_dto['companyId'] ?? "");
            $user_name = $login_dto['userName'] ?? "";
            $password = $login_dto['password'] ?? "";

            if ($req_company_id === strval($config_company_id)) {
                $user = DbHelper::findFirst(Users::class, "user_name = :username", ["username" => $user_name]);
                if ($user) {
                    $password_matched = ($user->password === $password || $this->check_django_password($password, $user->password));
                    if ($password_matched) {
                        $role_name = null;
                        if ($user->role) {
                            $role = DbHelper::findById(Roles::class, $user->role);
                            $role_name = $role ? $role->roleName : null;
                        }

                        $data = [
                            "userId" => $user->userId,
                            "userName" => $user->userName,
                            "firstName" => $user->firstName,
                            "lastName" => $user->lastName,
                            "middleName" => $user->middleName,
                            "email" => $user->email,
                            "phone" => $user->phone,
                            "gender" => $user->gender,
                            "hourlyRate" => $user->hourlyRate,
                            "personalIdentificationNumber" => $user->personalIdentificationNumber,
                            "address1" => $user->address1,
                            "address2" => $user->address2,
                            "city" => $user->city,
                            "zipCode" => $user->zipCode,
                            "country" => $user->country,
                            "state" => $user->state,
                            "birthDate" => $user->birthDate instanceof \DateTimeInterface ? $user->birthDate->format("Y-m-d") : ($user->birthDate ? date("Y-m-d", strtotime($user->birthDate)) : null),
                            "emergencyContact" => $user->emergencyContact,
                            "contactPhone" => $user->contactPhone,
                            "relationship" => $user->relationship,
                            "roleId" => $user->role,
                            "roleName" => $role_name,
                            "profileImage" => $user->profileImage,
                            "departmentId" => $user->department,
                        ];

                        $user_map = [
                            "userId" => strval($user->userId),
                            "userName" => $user->userName,
                            "roleId" => strval($user->role ?: ""),
                            "roleName" => $role_name ?: "",
                            "companyId" => strval($config_company_id),
                        ];

                        $token = $this->jwt_util->generate_token($user_map);
                        $res_body["token"] = $token;
                        $res_body["data"] = $data;
                        return $res_body;
                    } else {
                        $res_body["errorType"] = "password";
                        $res_body["error"] = "Invalid credentials.";
                        return $res_body;
                    }
                } else {
                    $res_body["error"] = "Invalid credentials.";
                    return $res_body;
                }
            } else {
                $company_details = DbHelper::findFirst(CompanyDetails::class, "company_no = :comp_no", ["comp_no" => $req_company_id]);
                if ($company_details && $company_details->isActive == 1) {
                    $company_employee = DbHelper::findFirst(CompanyEmployee::class, "company_id = :comp_id AND user_name = :username", [
                        "comp_id" => $company_details->id,
                        "username" => $user_name
                    ]);

                    if ($company_employee) {
                        // Geofence check
                        $role_name = "";
                        if ($company_employee->roles) {
                            $role = DbHelper::findById(CompanyEmployeeRoles::class, $company_employee->roles);
                            $role_name = $role ? $role->roleName : "";
                        }

                        if (!in_array($role_name, ["Admin", "Owner"]) && $company_employee->checkGeofence == 1) {
                            $comp_loc = $company_employee->companyLocation ?: "";
                            if (!empty($comp_loc)) {
                                $comp_loc_clean = str_replace(['[', ']'], '', $comp_loc);
                                $parts = array_filter(array_map('trim', explode(',', $comp_loc_clean)));
                                foreach ($parts as $p) {
                                    $location = DbHelper::findById(Locations::class, (int)$p);
                                    if ($location) {
                                        if (!$location->geofenceId) {
                                            $res_body["error"] = "Geofence data is missing or incomplete for one or more locations. Please contact your administrator to configure geofencing for your company's locations before proceeding.";
                                            return $res_body;
                                        }
                                    } else {
                                        $res_body["error"] = "Geofence data is missing or incomplete for one or more locations. Please contact your administrator to configure geofencing for your company's locations before proceeding.";
                                        return $res_body;
                                    }
                                }
                            } else {
                                $res_body["error"] = "Login failed due to internal error.";
                                return $res_body;
                            }
                        }

                        $password_matched = ($company_employee->password === $password || $this->check_django_password($password, $company_employee->password));
                        if ($password_matched) {
                            $theme_id = null;
                            $company_theme = DbHelper::findFirst(CompanyTheme::class, "company_id = :comp_id", ["comp_id" => $company_details->id]);
                            if ($company_theme) {
                                $theme_id = $company_theme->id;
                            }

                            $dept_name = null;
                            if ($company_employee->department) {
                                $dept = DbHelper::findById(Department::class, $company_employee->department);
                                $dept_name = $dept ? $dept->departmentName : null;
                            }

                            $company_employee_dto = [
                                "employeeId" => $company_employee->employeeId,
                                "userName" => $company_employee->userName,
                                "firstName" => $company_employee->firstName,
                                "lastName" => $company_employee->lastName,
                                "middleName" => $company_employee->middleName,
                                "email" => $company_employee->email,
                                "phone" => $company_employee->phone,
                                "gender" => $company_employee->gender,
                                "hourlyRate" => $company_employee->hourlyRate,
                                "address1" => $company_employee->address1,
                                "address2" => $company_employee->address2,
                                "city" => $company_employee->city,
                                "zipCode" => $company_employee->zipCode,
                                "country" => $company_employee->country,
                                "state" => $company_employee->state,
                                "dob" => $company_employee->dob instanceof \DateTimeInterface ? $company_employee->dob->format("Y-m-d") : ($company_employee->dob ? date("Y-m-d", strtotime($company_employee->dob)) : null),
                                "emergencyContact" => $company_employee->emergencyContact,
                                "contactPhone" => $company_employee->contactPhone,
                                "relationship" => $company_employee->relationship,
                                "roleId" => $company_employee->roles,
                                "roleName" => $role_name,
                                "profileImage" => $company_employee->profileImage,
                                "companyId" => $company_details->id,
                                "departmentName" => $dept_name,
                                "themeId" => $theme_id
                            ];

                            $user_map = [
                                "userId" => strval($company_employee->employeeId),
                                "userName" => $company_employee->userName,
                                "roleId" => strval($company_employee->roles ?: ""),
                                "roleName" => $role_name ?: "",
                                "companyId" => strval($company_details->id)
                            ];

                            $token = $this->jwt_util->generate_token($user_map);
                            $res_body["token"] = $token;
                            $res_body["data"] = $company_employee_dto;
                            return $res_body;
                        } else {
                            $res_body["error"] = "Invalid credentials.";
                            return $res_body;
                        }
                    } else {
                        $res_body["error"] = "User not found for company Id " . $company_details->companyNo;
                        return $res_body;
                    }
                } else {
                    $res_body["error"] = "Company Id is not valid";
                    return $res_body;
                }
            }
        } catch (Exception $e) {
            $res_body["error"] = "Login failed due to internal error.";
        }
        return $res_body;
    }

    public function generate_reset_link($email, $user_name, $id_str) {
        try {
            $config = require __DIR__ . '/../../config/settings.php';
            $config_company_id = $config['companyId'] ?? '108108';

            if (strval($id_str) === strval($config_company_id)) {
                $user = DbHelper::findFirst(Users::class, "email = :email AND user_name = :username", [
                    "email" => $email,
                    "username" => $user_name
                ]);
                if ($user && $user->userId) {
                    return $this->_generate_token($user->userId, $email, $config_company_id);
                }
            } else {
                $company_employee = DbHelper::findFirst(
                    CompanyEmployee::class,
                    "company_id IN (SELECT id FROM `company_details` WHERE company_no = :comp_no) AND user_name = :username AND email = :email",
                    [
                        "comp_no" => $id_str,
                        "username" => $user_name,
                        "email" => $email
                    ]
                );
                if ($company_employee) {
                    return $this->_generate_token($company_employee->employeeId, $email, $id_str);
                }
            }
            return false;
        } catch (Exception $e) {
            throw new Exception("Error generating reset link: " . $e->getMessage());
        }
    }

    private function _generate_token($user_id, $email, $company_no) {
        $current_timestamp = (int)(microtime(true) * 1000);
        $token_uuid = bin2hex(random_bytes(16));
        $data = "$company_no:$user_id:$token_uuid:$current_timestamp";
        
        $secret_key = "your-very-secret-key";
        $hmac_bytes = hash_hmac('sha256', $data, $secret_key, true);
        $hmac_b64 = $this->base64UrlEncode($hmac_bytes);
        
        $raw_token = "$data:$hmac_b64";
        $token_encoded = $this->base64UrlEncode($raw_token);

        $config = require __DIR__ . '/../../config/settings.php';
        $site_url = $config['siteUrl'] ?? 'http://localhost:3000/';
        if (substr($site_url, -1) !== '/') {
            $site_url .= '/';
        }
        $route = $site_url . "reset-pin/" . $token_encoded;

        $subject = "Reset Your Password - TimeSheetsPro";
        $body = "Hello,\n\n"
              . "We received a request to reset your password for your TimeSheetsPro account.\n"
              . "Please click the link below to reset your PIN:\n\n"
              . "$route\n\n"
              . "If you did not request this, you can safely ignore this email.\n\n"
              . "Thank you,\n"
              . "TimeSheetsPro Support Team";

        return $this->common_service->send_email($email, $subject, $body);
    }

    public function validate_token($token) {
        try {
            $decoded_token = $this->base64UrlDecode($token);
            $parts = explode(":", $decoded_token);
            if (count($parts) !== 5) {
                return [
                    "message" => "Invalid token structure",
                    "status" => 400
                ];
            }

            $company_no = $parts[0];
            $user_id_str = $parts[1];
            $token_uuid = $parts[2];
            $timestamp = (int)$parts[3];
            $provided_hmac_b64 = $parts[4];

            $user = DbHelper::findById(Users::class, (int)$user_id_str);
            if (!$user) {
                $company_employee = DbHelper::findById(CompanyEmployee::class, (int)$user_id_str);
                if (!$company_employee) {
                    return [
                        "message" => "User not found",
                        "status" => 404
                    ];
                }
            }

            $current_timestamp = (int)(microtime(true) * 1000);
            if ($current_timestamp - $timestamp > 180 * 1000) {
                return [
                    "message" => "Token is expired.",
                    "status" => 404
                ];
            }

            $data = "$company_no:$user_id_str:$token_uuid:$timestamp";
            $secret_key = "your-very-secret-key";
            $expected_hmac = hash_hmac('sha256', $data, $secret_key, true);
            $provided_hmac_bytes = $this->base64UrlDecode($provided_hmac_b64);

            if (hash_equals($expected_hmac, $provided_hmac_bytes)) {
                return [
                    "message" => "Token is valid",
                    "status" => 200,
                    "userId" => (int)$user_id_str
                ];
            }
            return null;
        } catch (Exception $e) {
            throw new Exception("Error validating token: " . $e->getMessage());
        }
    }

    public function reset_password($reset_password_dto) {
        $res_body = [];
        try {
            $token = $reset_password_dto['token'] ?? "";
            if (!empty($token)) {
                $decoded_token = $this->base64UrlDecode($token);
                $parts = explode(":", $decoded_token);
                if (count($parts) !== 5) {
                    return [
                        "message" => "Invalid token structure",
                        "status" => 400
                    ];
                }

                $company_no = $parts[0];
                $user_id_str = $parts[1];
            } else {
                $company_no = $reset_password_dto['companyId'] ?? null;
                $user_id_str = $reset_password_dto['employeeId'] ?? $reset_password_dto['userId'] ?? null;
                if ($company_no === null || $user_id_str === null) {
                    return [
                        "message" => "Invalid token structure",
                        "status" => 400
                    ];
                }
            }

            $config = require __DIR__ . '/../../config/settings.php';
            $config_company_id = $config['companyId'] ?? '108108';

            if (strval($company_no) === strval($config_company_id)) {
                $user = DbHelper::findById(Users::class, (int)$user_id_str);
                if (!$user) {
                    throw new Exception("User not found");
                }

                $curr_pwd = $reset_password_dto['currentPassword'] ?? null;
                if ($curr_pwd !== null) {
                    $password_matched = ($user->password === $curr_pwd || $this->check_django_password($curr_pwd, $user->password));
                    if (!$password_matched) {
                        $res_body["passwordNotMatch"] = "Current pin is wrong.";
                        return $res_body;
                    }
                }

                $user->password = $reset_password_dto['password'] ?? null;
                DbHelper::update($user);
                $res_body["success"] = "Pin change successfully.";
                return $res_body;
            } else {
                $company_employee = DbHelper::findById(CompanyEmployee::class, (int)$user_id_str);
                if (!$company_employee) {
                    throw new Exception("Company employee not found");
                }

                $curr_pwd = $reset_password_dto['currentPassword'] ?? null;
                if ($curr_pwd !== null) {
                    $password_matched = ($company_employee->password === $curr_pwd || $this->check_django_password($curr_pwd, $company_employee->password));
                    if (!$password_matched) {
                        $res_body["passwordNotMatch"] = "Current pin is wrong.";
                        return $res_body;
                    }
                }

                $company_employee->password = $reset_password_dto['password'] ?? null;
                DbHelper::update($company_employee);
                $res_body["success"] = "Pin change successfully.";
                return $res_body;
            }
        } catch (Exception $e) {
            throw new Exception("Error resetPassword: " . $e->getMessage());
        }
    }

    public function upload_profile_image($user_id, $image_path) {
        try {
            $this->delete_profile_image($user_id);
            $user = DbHelper::findById(Users::class, $user_id);
            if (!$user) {
                throw new Exception("User not found");
            }

            $updated_path = $this->common_service->update_file_location_for_profile($image_path, $user_id, "profileImages");
            if ($updated_path === "Error") {
                return "Error";
            }

            $user->profileImage = $updated_path;
            DbHelper::update($user);
            return $updated_path;
        } catch (Exception $e) {
            throw new Exception("Error uploadProfileImage: " . $e->getMessage());
        }
    }

    public function delete_profile_image($user_id) {
        try {
            $user = DbHelper::findById(Users::class, $user_id);
            if (!$user) {
                throw new Exception("User not found");
            }

            $user->profileImage = "";
            DbHelper::update($user);

            $config = require __DIR__ . '/../../config/settings.php';
            $file_dir = $config['timesheetpro_drive'] ?? '';

            $existing_image_path = $file_dir . DIRECTORY_SEPARATOR . $user_id . DIRECTORY_SEPARATOR . "profileImages";
            if (file_exists($existing_image_path)) {
                $this->common_service->delete_directory_recursively($existing_image_path);
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw new Exception("Error deleteProfileImage: " . $e->getMessage());
        }
    }
}
