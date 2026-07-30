<?php
namespace Common\Views;

use Common\Services\CountryService;
use Common\Response\ApiResponse;
use Exception;

class CountryController {
    private $service;

    public function __construct() {
        $this->service = new CountryService();
    }

    public function get_all_country() {
        try {
            $result = $this->service->get_all_country();
            return ApiResponse::send(200, "Fetch country details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch country details", []);
        }
    }

    public function get_country($id) {
        try {
            $country_id = (int)$id;
            $result = $this->service->get_country($country_id);
            return ApiResponse::send(200, "Fetch country details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch country details", []);
        }
    }
}
