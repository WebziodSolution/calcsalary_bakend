<?php
namespace Common\Exception;

use Common\Response\ApiResponse;
use Exception;

class ExceptionHandler {
    /**
     * Handles exceptions and maps them to standard JSON api envelopes.
     * 
     * @param Exception $exc The caught exception
     */
    public static function handle(Exception $exc) {
        if ($exc instanceof GlobalException) {
            return ApiResponse::send(400, $exc->getMessage(), null);
        }

        // Catch other exceptions and return standard internal server error envelope
        return ApiResponse::send(500, $exc->getMessage(), null);
    }
}
