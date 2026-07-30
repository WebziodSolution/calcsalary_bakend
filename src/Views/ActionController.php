<?php
namespace Common\Views;

use Common\Services\ActionService;
use Common\Response\ApiResponse;
use Exception;

class ActionController {
    private $service;

    public function __construct() {
        $this->service = new ActionService();
    }

    public function get_all_actions() {
        try {
            $result = $this->service->getAllActions();
            return ApiResponse::send(200, "Fetch actions details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch actions details", []);
        }
    }
}
