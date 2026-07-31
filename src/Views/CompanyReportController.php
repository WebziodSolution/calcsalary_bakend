<?php
namespace Common\Views;

use Common\Services\CompanyReportService;
use Common\Response\ApiResponse;
use Exception;

class CompanyReportController {
    private $service;

    public function __construct() {
        $this->service = new CompanyReportService();
    }

    public function get_filtered_companies() {
        try {
            $input = array_merge($_GET, $_POST, json_decode(file_get_contents('php://input'), true) ?? []);
            
            $start_date = $input['startDate'] ?? null;
            $end_date = $input['endDate'] ?? null;
            
            $min_val_str = $input['min'] ?? null;
            $max_val_str = $input['max'] ?? null;
            $time_zone = $input['timeZone'] ?? null;
            
            $page_str = $input['page'] ?? null;
            $size_str = $input['size'] ?? null;

            $min_val = ($min_val_str !== null && $min_val_str !== '') ? (int)$min_val_str : null;
            $max_val = ($max_val_str !== null && $max_val_str !== '') ? (int)$max_val_str : null;
            
            $page = ($page_str !== null && $page_str !== '') ? (int)$page_str : 0;
            $size = ($size_str !== null && $size_str !== '') ? (int)$size_str : 10;

            $result = $this->service->getCompanies($start_date, $end_date, $min_val, $max_val, $page, $size, $time_zone);
            return ApiResponse::send(200, "Companies fetched successfully", $result);
        } catch (Exception $e) {
            return ApiResponse::send(500, "Failed to fetch companies", []);
        }
    }
}
