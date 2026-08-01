<?php
namespace Common\Auth;

use Common\Exception\GlobalException;
use PDO;

class JWTAuthentication {
    private $jwt_util;
    private $db;

    public function __construct(PDO $db) {
        $this->jwt_util = new JwtTokenUtil();
        $this->db = $db;
    }

    /**
     * Public paths bypassing JWT security.
     */
    public function is_public_path($path) {
        $public_patterns = [
            '/^\/location/',
            '/^\/inout\/clockInOut/',
            '/^\/getTimezones/',
            '/^\/uploadFile/',
            '/^\/user\/uploadProfileImage/',
            '/^\/user\/login/',
            '/^\/user\/resetPassword/',
            '/^\/user\/validateToken/',
            '/^\/user\/generateResetLink/',
            '/^\/user\/create/',
            '/^\/companyDetails\/create/',
            '/^\/api\/schema/',
            '/^\/state/',
            '/^\/country/',
            '/^\/companyEmployee\/getAllCompanyEmployee/'
        ];

        foreach ($public_patterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Performs authentication checks on headers.
     */
    public function authenticate($request_uri) {
        // Normalize request URI by removing trailing slash if not root
        if ($request_uri !== '/' && substr($request_uri, -1) === '/') {
            $request_uri = substr($request_uri, 0, -1);
        }

        if ($this->is_public_path($request_uri)) {
            return null;
        }

        $headers = getallheaders();
        $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);

        // Fallback to query param or server headers
        if (!$auth_header && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (!$auth_header) {
            if ($this->is_public_path($request_uri)) {
                return null;
            }
            throw new GlobalException("Access Denied");
        }

        if (strpos($auth_header, 'Bearer ') !== 0) {
            if ($this->is_public_path($request_uri)) {
                return null;
            }
            throw new GlobalException("Access Denied");
        }

        $token = substr($auth_header, 7);
        try {
            $username = $this->jwt_util->extract_username($token);
            $company_id = $this->jwt_util->extract_company_id($token);
            $user_id = $this->jwt_util->extract_user_id($token);

            if (!$username) {
                throw new GlobalException("Access Denied");
            }

            $user = null;
            if (empty($company_id)) {
                $stmt = $this->db->prepare("SELECT * FROM users WHERE user_Id = :user_id LIMIT 1");
                $stmt->execute(['user_id' => $user_id]);
                $user = $stmt->fetch();
            } else {
                $stmt = $this->db->prepare("SELECT * FROM company_employees WHERE id = :user_id LIMIT 1");
                $stmt->execute(['user_id' => $user_id]);
                $user = $stmt->fetch();
            }

            if (!$user) {
                throw new GlobalException("Access Denied");
            }

            $user_data = [
                "userName" => $user['user_name'] ?? ($user['userName'] ?? ''),
                "email" => $user['email'] ?? ''
            ];

            if (!$this->jwt_util->validate_token($token, $user_data)) {
                throw new GlobalException("Access Denied");
            }

            // Bind values globally in $_SERVER or constants for view access
            $_SERVER['AUTH_USER_ID'] = $user_id;
            $_SERVER['AUTH_COMPANY_ID'] = $company_id;

            return [
                "user" => $user,
                "token" => $token,
                "user_id" => $user_id,
                "company_id" => $company_id
            ];
        } catch (\Exception $e) {
            throw new GlobalException("Access Denied");
        }
    }
}
