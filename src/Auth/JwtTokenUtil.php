<?php
namespace Common\Auth;

use DateTime;
use Exception;

class JwtTokenUtil {
    private $secret_key;
    private $expiration_hours;

    public function __construct() {
        $config = require __DIR__ . '/../../config/settings.php';
        $this->secret_key = $config['jwt_secret_key'];
        $this->expiration_hours = isset($config['jwt_expiration_hours']) ? $config['jwt_expiration_hours'] : 10;
    }

    private function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode($data) {
        $b64 = str_replace(['-', '_'], ['+', '/'], $data);
        $padded = str_pad($b64, strlen($b64) % 4, '=', STR_PAD_RIGHT);
        return base64_decode($padded);
    }

    /**
     * Generate a JWT token for a given user representation dictionary.
     */
    public function generate_token($user) {
        $iat = time();
        $exp = $iat + ($this->expiration_hours * 3600);

        $header = [
            "alg" => "HS256",
            "typ" => "JWT"
        ];

        $payload = [
            "userId" => strval($user["userId"] ?? ""),
            "roleId" => strval($user["roleId"] ?? ""),
            "roleName" => strval($user["roleName"] ?? ""),
            "role" => strval($user["roleName"] ?? ""),  // for compatibility
            "companyId" => strval($user["companyId"] ?? ""),
            "userName" => strval($user["userName"] ?? ""),
            "sub" => strval($user["userName"] ?? ""),
            "iat" => $iat,
            "exp" => $exp
        ];

        $encoded_header = $this->base64UrlEncode(json_encode($header));
        $encoded_payload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "$encoded_header.$encoded_payload", $this->secret_key, true);
        $encoded_signature = $this->base64UrlEncode($signature);

        return "$encoded_header.$encoded_payload.$encoded_signature";
    }

    /**
     * Decodes token and returns claims.
     */
    public function decode_token($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception("Invalid token structure");
        }

        list($header, $payload, $signature) = $parts;

        $valid_signature = hash_hmac('sha256', "$header.$payload", $this->secret_key, true);
        if ($this->base64UrlEncode($valid_signature) !== $signature) {
            throw new Exception("Invalid signature");
        }

        $decoded_payload = json_decode($this->base64UrlDecode($payload), true);
        if (!$decoded_payload) {
            throw new Exception("Invalid payload");
        }

        if (isset($decoded_payload['exp']) && $decoded_payload['exp'] < time()) {
            throw new Exception("Token has expired");
        }

        return $decoded_payload;
    }

    public function extract_username($token) {
        try {
            $payload = $this->decode_token($token);
            return $payload['sub'] ?? "";
        } catch (Exception $e) {
            return "";
        }
    }

    public function extract_user_id($token) {
        try {
            $payload = $this->decode_token($token);
            return intval($payload['userId'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    public function extract_member_role($token) {
        try {
            $payload = $this->decode_token($token);
            return $payload['role'] ?? ($payload['roleName'] ?? "");
        } catch (Exception $e) {
            return "";
        }
    }

    public function extract_company_id($token) {
        try {
            $payload = $this->decode_token($token);
            return $payload['companyId'] ?? "";
        } catch (Exception $e) {
            return "";
        }
    }

    public function extract_expiration($token) {
        try {
            $payload = $this->decode_token($token);
            return isset($payload['exp']) ? new DateTime("@" . $payload['exp']) : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function is_token_expired($token) {
        try {
            $this->decode_token($token);
            return false;
        } catch (Exception $e) {
            return true;
        }
    }

    public function validate_token($token, $user_data) {
        try {
            $payload = $this->decode_token($token);
            $username = $payload['sub'] ?? "";
            return ($username === ($user_data['email'] ?? null) || $username === ($user_data['userName'] ?? null));
        } catch (Exception $e) {
            return false;
        }
    }
}
