<?php
namespace Common\Exception;

use Common\Response\ApiResponse;
use Exception;

class ExceptionHandler {
    /**
     * Handles exceptions and maps them to standard JSON api envelopes.
     * 
     * @param \Throwable $exc The caught throwable
     */
    public static function handle(\Throwable $exc) {
        if ($exc instanceof GlobalException) {
            return ApiResponse::send(400, $exc->getMessage(), null);
        }

        // Catch other exceptions/errors and return standard internal server error envelope
        return ApiResponse::send(500, $exc->getMessage(), null);
    }
}
