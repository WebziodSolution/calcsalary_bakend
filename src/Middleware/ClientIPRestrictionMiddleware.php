<?php
namespace Common\Middleware;

use Common\Response\ApiResponse;

class ClientIPRestrictionMiddleware {
    /**
     * Inspects client IP address (considering reverse proxy headers) and enforces IP access restrictions.
     */
    public static function handle() {
        $ip = null;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (!$ip) {
            ApiResponse::send(403, "Access Denied: Client IP address cannot be determined.");
        }

        // Load configuration from settings
        $config = require __DIR__ . '/../../config/settings.php';

        $restrict_ips = isset($config['restrict_ips']) ? $config['restrict_ips'] : true;
        if (!$restrict_ips) {
            return;
        }

        $allowed_ips = isset($config['allowed_client_ips']) ? $config['allowed_client_ips'] : ['127.0.0.1', '::1'];
        $allowed_prefixes = isset($config['allowed_client_ip_prefixes']) ? $config['allowed_client_ip_prefixes'] : ['192.168.1.'];

        $is_allowed = false;
        if (in_array($ip, $allowed_ips)) {
            $is_allowed = true;
        } else {
            foreach ($allowed_prefixes as $prefix) {
                if (strpos($ip, $prefix) === 0) {
                    $is_allowed = true;
                    break;
                }
            }
        }

        if (!$is_allowed) {
            ApiResponse::send(403, "Access Denied: IP $ip is not whitelisted.");
        }
    }
}
