<?php
namespace Common\Views;

use Common\Services\LocationService;
use Common\Response\ApiResponse;
use Exception;

class LocationController {
    private $service;

    public function __construct() {
        $this->service = new LocationService();
    }

    public function get_company_active_locations($id) {
        try {
            $company_id = (int)$id;
            $result = $this->service->get_company_active_locations($company_id);
            return ApiResponse::send(200, "Fetch locations details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch locations details", []);
        }
    }

    public function get_all_location_by_company($id) {
        try {
            $company_id = (int)$id;
            $result = $this->service->get_all_location_by_company($company_id);
            return ApiResponse::send(200, "Fetch locations details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch locations details", []);
        }
    }

    public function get_all_location() {
        try {
            $result = $this->service->get_all_location();
            return ApiResponse::send(200, "Fetch locations details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch locations details", []);
        }
    }

    public function get_locations() {
        try {
            $location_ids_param = $_GET['locationIds'] ?? [];
            
            // Support comma-separated parameter values
            $location_ids = [];
            if (is_array($location_ids_param)) {
                foreach ($location_ids_param as $val) {
                    if (strpos($val, ',') !== false) {
                        $location_ids = array_merge($location_ids, array_map('intval', explode(',', $val)));
                    } else {
                        $location_ids[] = (int)$val;
                    }
                }
            } else {
                if (strpos($location_ids_param, ',') !== false) {
                    $location_ids = array_map('intval', explode(',', $location_ids_param));
                } else if ($location_ids_param !== "") {
                    $location_ids[] = (int)$location_ids_param;
                }
            }

            $result = $this->service->get_locations($location_ids);
            return ApiResponse::send(200, "Fetched location details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Failed to fetch location details", []);
        }
    }

    public function get_location($id) {
        try {
            $location_id = (int)$id;
            $result = $this->service->get_location($location_id);
            return ApiResponse::send(200, "Fetch locations details successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to fetch locations details", []);
        }
    }

    public function create_location() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $this->service->create_location($input);
            return ApiResponse::send(201, "Locations details added successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to add locations details", []);
        }
    }

    public function update_location($id) {
        try {
            $location_id = (int)$id;
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            $this->service->update_location($location_id, $input);
            return ApiResponse::send(200, "Locations details updated successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to updated locations details", []);
        }
    }

    public function delete_location($id) {
        try {
            $location_id = (int)$id;
            $this->service->delete_location($location_id);
            return ApiResponse::send(200, "Locations details deleted successfully", "");
        } catch (Exception $e) {
            return ApiResponse::send(500, "Fail to delete locations details", []);
        }
    }
}
