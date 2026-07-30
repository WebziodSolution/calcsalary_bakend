<?php
namespace Common\Services;

use Common\Models\Locations;
use Common\Models\CompanyDetails;
use Common\Serializers\LocationSerializer;
use Exception;

class LocationService {
    private $common_service;

    public function __construct() {
        $this->common_service = new CommonService();
    }

    private function _convert_model_to_dto(Locations $loc) {
        $pay_period_start_str = null;
        $pay_period_end_str = null;

        if ($loc->payPeriodStart) {
            $pay_period_start_str = $loc->payPeriodStart instanceof \DateTimeInterface ? $loc->payPeriodStart->format("Y-m-d") : date("Y-m-d", strtotime($loc->payPeriodStart));
        }
        if ($loc->payPeriodEnd) {
            $pay_period_end_str = $loc->payPeriodEnd instanceof \DateTimeInterface ? $loc->payPeriodEnd->format("Y-m-d") : date("Y-m-d", strtotime($loc->payPeriodEnd));
        }

        $dto = new LocationSerializer();
        $dto->id = $loc->id;
        $dto->locationName = $loc->locationName;
        $dto->city = $loc->city;
        $dto->state = $loc->state;
        $dto->country = $loc->country;
        $dto->address1 = $loc->address1;
        $dto->address2 = $loc->address2;
        $dto->employeeCount = $loc->employeeCount;
        $dto->zipCode = $loc->zipCode;
        $dto->companyId = $loc->companyDetails;
        $dto->externalId = $loc->externalId;
        $dto->geofenceId = $loc->geofenceId;
        $dto->isActive = $loc->isActive;
        $dto->payPeriod = $loc->payPeriod;
        $dto->payPeriodStart = $pay_period_start_str;
        $dto->payPeriodEnd = $pay_period_end_str;

        return (array)$dto;
    }

    public function get_company_active_locations($company_id) {
        try {
            $locations_list = DbHelper::findAll(Locations::class, "company_id = :comp_id AND is_active = 1", ["comp_id" => $company_id], "id ASC");
            $response = [];
            foreach ($locations_list as $loc) {
                $response[] = $this->get_location($loc->id);
            }
            return $response;
        } catch (Exception $e) {
            throw new Exception("Error :" . $e->getMessage());
        }
    }

    public function get_all_location_by_company($company_id) {
        try {
            $locations_list = DbHelper::findAll(Locations::class, "company_id = :comp_id", ["comp_id" => $company_id], "id ASC");
            $response = [];
            foreach ($locations_list as $loc) {
                $response[] = $this->get_location($loc->id);
            }
            return $response;
        } catch (Exception $e) {
            throw new Exception("Error :" . $e->getMessage());
        }
    }

    public function get_all_location() {
        try {
            $locations_list = DbHelper::findAll(Locations::class, "1=1", [], "id ASC");
            $response = [];
            foreach ($locations_list as $loc) {
                $response[] = $this->get_location($loc->id);
            }
            return $response;
        } catch (Exception $e) {
            throw new Exception("Error :" . $e->getMessage());
        }
    }

    public function get_locations($ids) {
        try {
            if (empty($ids)) {
                throw new Exception("No locations found for given IDs");
            }
            $placeholders = [];
            $params = [];
            foreach ($ids as $idx => $id) {
                $key = "id_" . $idx;
                $placeholders[] = ":" . $key;
                $params[$key] = $id;
            }
            $in_sql = implode(", ", $placeholders);
            
            $locations_list = DbHelper::findAll(Locations::class, "id IN ($in_sql)", $params, "id ASC");
            if (empty($locations_list)) {
                throw new Exception("No locations found for given IDs");
            }

            $dtos = [];
            foreach ($locations_list as $loc) {
                $pay_start = $loc->payPeriodStart instanceof \DateTimeInterface ? $loc->payPeriodStart->format("Y-m-d") : ($loc->payPeriodStart ? date("Y-m-d", strtotime($loc->payPeriodStart)) : null);
                $pay_end = $loc->payPeriodEnd instanceof \DateTimeInterface ? $loc->payPeriodEnd->format("Y-m-d") : ($loc->payPeriodEnd ? date("Y-m-d", strtotime($loc->payPeriodEnd)) : null);

                $dtos[] = [
                    "id" => $loc->id,
                    "locationName" => $loc->locationName,
                    "city" => $loc->city,
                    "state" => $loc->state,
                    "country" => $loc->country,
                    "address1" => $loc->address1,
                    "address2" => $loc->address2,
                    "employeeCount" => $loc->employeeCount,
                    "zipCode" => $loc->zipCode,
                    "companyId" => $loc->companyDetails,
                    "externalId" => $loc->externalId,
                    "geofenceId" => $loc->geofenceId,
                    "isActive" => $loc->isActive,
                    "payPeriod" => $loc->payPeriod,
                    "payPeriodStart" => $pay_start,
                    "payPeriodEnd" => $pay_end
                ];
            }
            return $dtos;
        } catch (Exception $e) {
            throw new Exception("Error: " . $e->getMessage());
        }
    }

    public function get_location($id) {
        try {
            $loc = DbHelper::findById(Locations::class, $id);
            if (!$loc) {
                throw new Exception("Location not found");
            }
            return $this->_convert_model_to_dto($loc);
        } catch (Exception $e) {
            throw new Exception("Error :" . $e->getMessage());
        }
    }

    private function _is_not_empty($val) {
        return $val !== null && trim(strval($val)) !== "";
    }

    public function create_location($dto) {
        try {
            $company_id = $dto['companyId'] ?? null;
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            $loc = new Locations();
            $loc->companyDetails = $company_id;
            $loc->locationName = $dto['locationName'] ?? null;
            $loc->city = $dto['city'] ?? null;
            $loc->state = $dto['state'] ?? null;
            $loc->country = $dto['country'] ?? null;
            $loc->address1 = $dto['address1'] ?? null;
            $loc->address2 = $dto['address2'] ?? null;
            $loc->employeeCount = $dto['employeeCount'] ?? null;
            $loc->zipCode = $dto['zipCode'] ?? null;
            $loc->externalId = $dto['externalId'] ?? null;
            $loc->geofenceId = $dto['geofenceId'] ?? null;
            $loc->payPeriod = $dto['payPeriod'] ?? null;

            if (!empty($dto['payPeriodStart'])) {
                $loc->payPeriodStart = $this->common_service->convert_string_to_date($dto['payPeriodStart']);
            }
            if (!empty($dto['payPeriodEnd'])) {
                $loc->payPeriodEnd = $this->common_service->convert_string_to_date($dto['payPeriodEnd']);
            }

            if (
                $this->_is_not_empty($dto['locationName'] ?? '') &&
                $this->_is_not_empty($dto['city'] ?? '') &&
                $this->_is_not_empty($dto['country'] ?? '') &&
                $this->_is_not_empty($dto['state'] ?? '') &&
                $this->_is_not_empty($dto['address1'] ?? '') &&
                $this->_is_not_empty($dto['zipCode'] ?? '') &&
                $this->_is_not_empty($dto['geofenceId'] ?? '') &&
                $this->_is_not_empty($dto['externalId'] ?? '')
            ) {
                $loc->isActive = 1;
            } else {
                $loc->isActive = 0;
            }

            DbHelper::insert($loc);
            return $dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_location($id, $dto) {
        try {
            $loc = DbHelper::findById(Locations::class, $id);
            if (!$loc) {
                throw new Exception("Location not found");
            }

            $company_id = $dto['companyId'] ?? null;
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            $loc->companyDetails = $company_id;
            $loc->city = $dto['city'] ?? null;
            $loc->state = $dto['state'] ?? null;
            $loc->country = $dto['country'] ?? null;
            $loc->address1 = $dto['address1'] ?? null;
            $loc->address2 = $dto['address2'] ?? null;
            $loc->employeeCount = $dto['employeeCount'] ?? null;
            $loc->zipCode = $dto['zipCode'] ?? null;
            $loc->locationName = $dto['locationName'] ?? null;
            $loc->geofenceId = $dto['geofenceId'] ?? null;
            $loc->externalId = $dto['externalId'] ?? null;
            $loc->payPeriod = $dto['payPeriod'] ?? null;

            if (!empty($dto['payPeriodStart'])) {
                $loc->payPeriodStart = $this->common_service->convert_string_to_date($dto['payPeriodStart']);
            } else {
                $loc->payPeriodStart = null;
            }
            if (!empty($dto['payPeriodEnd'])) {
                $loc->payPeriodEnd = $this->common_service->convert_string_to_date($dto['payPeriodEnd']);
            } else {
                $loc->payPeriodEnd = null;
            }

            if (
                $this->_is_not_empty($dto['locationName'] ?? '') &&
                $this->_is_not_empty($dto['city'] ?? '') &&
                $this->_is_not_empty($dto['country'] ?? '') &&
                $this->_is_not_empty($dto['state'] ?? '') &&
                $this->_is_not_empty($dto['address1'] ?? '') &&
                $this->_is_not_empty($dto['zipCode'] ?? '') &&
                $this->_is_not_empty($dto['geofenceId'] ?? '') &&
                $this->_is_not_empty($dto['externalId'] ?? '')
            ) {
                $loc->isActive = 1;
            } else {
                $loc->isActive = 0;
            }

            DbHelper::update($loc);
            return $dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_location($id) {
        try {
            $loc = DbHelper::findById(Locations::class, $id);
            if (!$loc) {
                throw new Exception("Location not found");
            }
            DbHelper::delete(Locations::class, $id);
        } catch (Exception $e) {
            throw new Exception("Error :" . $e->getMessage());
        }
    }
}
