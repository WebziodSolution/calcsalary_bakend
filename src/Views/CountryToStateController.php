<?php
namespace Common\Views;

use Common\Services\CountryToStateService;
use Common\Response\ApiResponse;
use Exception;

class CountryToStateController {
    private $service;

    public function __construct() {
        $this->service = new CountryToStateService();
    }

    public function get_all_state() {
        try {
            $result = $this->service->get_all_state();
            return ApiResponse::send(200, "Fetch state details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch state details", []);
        }
    }

    public function get_all_state_by_country($id) {
        try {
            $country_id = (int)$id;
            $result = $this->service->get_all_state_by_country($country_id);
            return ApiResponse::send(200, "Fetch state details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch state details", []);
        }
    }
}
