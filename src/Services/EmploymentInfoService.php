<?php
namespace Common\Services;

use Common\Models\EmploymentInfo;
use Common\Models\CompanyEmployee;
use Common\Serializers\EmploymentInfoSerializer;
use Exception;

class EmploymentInfoService {
    private $common_service;

    public function __construct() {
        $this->common_service = new CommonService();
    }

    public function get_employment_info_by_id($id) {
        try {
            $entity = DbHelper::findById(EmploymentInfo::class, $id);
            if (!$entity) {
                throw new Exception("EmploymentInfo not found");
            }

            $dto = new EmploymentInfoSerializer();
            $dto->id = $entity->id;
            $dto->workPhone = $entity->workPhone;
            $dto->ext = $entity->ext;
            $dto->workEmail = $entity->workEmail;
            
            $hire_date_str = null;
            if ($entity->hireDate) {
                $hire_date_str = $entity->hireDate instanceof \DateTimeInterface ? $entity->hireDate->format("Y-m-d") : date("Y-m-d", strtotime($entity->hireDate));
            }
            $dto->hireDate = $hire_date_str;
            $dto->status = $entity->status;
            $dto->paidPension = $entity->paidPension;
            $dto->statutoryEmployee = $entity->statutoryEmployee;
            $dto->exclusionIndicator = $entity->exclusionIndicator;
            $dto->keyEmployeeIndicator = $entity->keyEmployeeIndicator;
            $dto->hce = $entity->hce;
            $dto->unionIndicator = $entity->unionIndicator;
            $dto->eligibilityIndicator = $entity->eligibilityIndicator;
            $dto->employeeId = $entity->companyEmployee;

            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_employment_info() {
        try {
            $entities = DbHelper::findAll(EmploymentInfo::class, "1=1", [], "id ASC");
            $dto_list = [];
            foreach ($entities as $entity) {
                $dto_list[] = $this->get_employment_info_by_id($entity->id);
            }
            return $dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_employment_info($dto) {
        try {
            $employee_id = $dto['employeeId'] ?? null;
            $employee = DbHelper::findById(CompanyEmployee::class, $employee_id);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $hire_date = null;
            if (!empty($dto['hireDate'])) {
                $hire_date = $this->common_service->convert_string_to_date($dto['hireDate']);
            }

            $entity = new EmploymentInfo();
            $entity->companyEmployee = $employee_id;
            $entity->workPhone = $dto['workPhone'] ?? null;
            $entity->ext = $dto['ext'] ?? null;
            $entity->workEmail = $dto['workEmail'] ?? null;
            $entity->hireDate = $hire_date;
            $entity->status = $dto['status'] ?? null;
            $entity->paidPension = $dto['paidPension'] ?? null;
            $entity->statutoryEmployee = $dto['statutoryEmployee'] ?? null;
            $entity->exclusionIndicator = $dto['exclusionIndicator'] ?? null;
            $entity->keyEmployeeIndicator = $dto['keyEmployeeIndicator'] ?? null;
            $entity->hce = $dto['hce'] ?? null;
            $entity->unionIndicator = $dto['unionIndicator'] ?? null;
            $entity->eligibilityIndicator = $dto['eligibilityIndicator'] ?? null;

            $entity = DbHelper::insert($entity);
            return $this->get_employment_info_by_id($entity->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_employment_info($id, $dto) {
        try {
            $entity = DbHelper::findById(EmploymentInfo::class, $id);
            if (!$entity) {
                throw new Exception("EmploymentInfo not found");
            }

            $employee_id = $dto['employeeId'] ?? null;
            $employee = DbHelper::findById(CompanyEmployee::class, $employee_id);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $hire_date = null;
            if (!empty($dto['hireDate'])) {
                $hire_date = $this->common_service->convert_string_to_date($dto['hireDate']);
            }

            $entity->companyEmployee = $employee_id;
            $entity->workPhone = $dto['workPhone'] ?? null;
            $entity->ext = $dto['ext'] ?? null;
            $entity->workEmail = $dto['workEmail'] ?? null;
            $entity->hireDate = $hire_date;
            $entity->status = $dto['status'] ?? null;
            $entity->paidPension = $dto['paidPension'] ?? null;
            $entity->statutoryEmployee = $dto['statutoryEmployee'] ?? null;
            $entity->exclusionIndicator = $dto['exclusionIndicator'] ?? null;
            $entity->keyEmployeeIndicator = $dto['keyEmployeeIndicator'] ?? null;
            $entity->hce = $dto['hce'] ?? null;
            $entity->unionIndicator = $dto['unionIndicator'] ?? null;
            $entity->eligibilityIndicator = $dto['eligibilityIndicator'] ?? null;

            DbHelper::update($entity);
            return $this->get_employment_info_by_id($entity->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_employment_info($id) {
        try {
            $entity = DbHelper::findById(EmploymentInfo::class, $id);
            if (!$entity) {
                throw new Exception("EmploymentInfo not found");
            }
            DbHelper::delete(EmploymentInfo::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
