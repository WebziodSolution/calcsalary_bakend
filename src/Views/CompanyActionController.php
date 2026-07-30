<?php
namespace Common\Views;

use Common\Services\CompanyActionService;
use Common\Response\ApiResponse;
use Exception;

class CompanyActionController {
    private $service;

    public function __construct() {
        $this->service = new CompanyActionService();
    }

    public function get_all_actions() {
        try {
            $result = $this->service->get_all_actions();
            return ApiResponse::send(200, "Fetch actions details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch actions details", []);
        }
    }
}
