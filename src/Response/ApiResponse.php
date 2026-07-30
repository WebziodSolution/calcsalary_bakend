<?php
namespace Common\Response;

class ApiResponse {
    /**
     * Sends a JSON response with status, message and result, then exits.
     * Matches the structure of the Python/Java counterparts.
     * 
     * @param int $status HTTP status code
     * @param string $message Response description
     * @param mixed $result Body payload
     */
    public static function send($status, $message, $result = null) {
        if (ob_get_level()) {
            ob_clean();
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        $data = [
            "status" => $status,
            "message" => $message,
            "result" => $result
        ];

        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
