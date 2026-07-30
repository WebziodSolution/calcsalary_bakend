<?php
namespace Common\Auth;

use Common\Exception\GlobalException;
use PDO;

class CustomAuthenticateManager {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Django PBKDF2 SHA256 Password Verification in PHP.
     */
    private function verifyDjangoPBKDF2($password, $stored_pwd) {
        if (strpos($stored_pwd, 'pbkdf2_sha256$') === 0) {
            $parts = explode('$', $stored_pwd);
            if (count($parts) === 4) {
                $iterations = intval($parts[1]);
                $salt = $parts[2];
                $hash = $parts[3];
                // Django PBKDF2 SHA256 hashes the password using HMAC-SHA256, then encodes in base64
                $calc_hash = base64_encode(hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true));
                return hash_equals($hash, $calc_hash);
            }
        }
        return false;
    }

    /**
     * Authenticates user against users and company_employees tables.
     */
    public function authenticate($username, $password, $company_id = null) {
        $user = null;
        $user_type = 'Users';

        // 1. Search in Users table first (global users)
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE user_name = :username OR email = :email LIMIT 1");
            $stmt->execute(['username' => $username, 'email' => $username]);
            $user = $stmt->fetch();
        } catch (\Exception $e) {
            // Ignore database exception and proceed
        }

        // 2. Search in CompanyEmployee table (employees belonging to a company)
        if (!$user) {
            $user_type = 'CompanyEmployee';
            try {
                $query = "SELECT * FROM company_employees WHERE 1=1";
                $params = [];
                if ($company_id) {
                    $query .= " AND company_id = :company_id";
                    $params['company_id'] = $company_id;
                }
                $query .= " AND (user_name = :username OR email = :email) LIMIT 1";
                $params['username'] = $username;
                $params['email'] = $username;

                $stmt = $this->db->prepare($query);
                $stmt->execute($params);
                $user = $stmt->fetch();
            } catch (\Exception $e) {
                // Ignore database exception and proceed
            }
        }

        if ($user) {
            $stored_pwd = $user['password'] ?? '';
            
            // Replicating secure password validation supporting:
            // - Plain text (Spring Boot fallback)
            // - Django PBKDF2
            // - Standard PHP password_verify (bcrypt/argon2)
            $password_valid = ($stored_pwd === $password) 
                              || $this->verifyDjangoPBKDF2($password, $stored_pwd)
                              || (password_get_info($stored_pwd)['algo'] && password_verify($password, $stored_pwd));

            if ($password_valid) {
                $role_name = "";
                $role_id = "";

                if ($user_type === 'Users') {
                    // Fetch role from roles table
                    if (isset($user['role_id']) && $user['role_id']) {
                        $role_stmt = $this->db->prepare("SELECT roleId, roleName FROM roles WHERE roleId = :role_id LIMIT 1");
                        $role_stmt->execute(['role_id' => $user['role_id']]);
                        $role = $role_stmt->fetch();
                        if ($role) {
                            $role_name = $role['roleName'] ?? '';
                            $role_id = $role['roleId'] ?? '';
                        }
                    }
                } elseif ($user_type === 'CompanyEmployee') {
                    // Fetch role from company_employee_roles table
                    if (isset($user['role_id']) && $user['role_id']) {
                        $role_stmt = $this->db->prepare("SELECT id, role_name FROM company_employee_roles WHERE id = :role_id LIMIT 1");
                        $role_stmt->execute(['role_id' => $user['role_id']]);
                        $role = $role_stmt->fetch();
                        if ($role) {
                            $role_name = $role['role_name'] ?? '';
                            $role_id = $role['id'] ?? '';
                        }
                    }
                }

                $company_val = "";
                if ($user_type === 'CompanyEmployee' && isset($user['company_id'])) {
                    $company_val = $user['company_id'];
                }

                $user_id = $user['user_Id'] ?? ($user['id'] ?? null);

                return [
                    "userId" => strval($user_id ?? ""),
                    "roleId" => strval($role_id ?? ""),
                    "roleName" => strval($role_name ?? ""),
                    "companyId" => strval($company_val ?? ""),
                    "userName" => $user['user_name'] ?? ($user['userName'] ?? ''),
                    "email" => $user['email'] ?? "",
                    "type" => $user_type,
                    "object" => $user
                ];
            } else {
                throw new GlobalException("Invalid Credentials");
            }
        } else {
            throw new GlobalException("User Not Found");
        }
    }
}
