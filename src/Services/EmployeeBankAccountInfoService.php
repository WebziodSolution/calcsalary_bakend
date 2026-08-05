<?php
namespace Common\Services;

use Common\Models\EmployeeBackAccountInfo;
use Common\Models\CompanyEmployee;
use Common\Serializers\EmployeeBackAccountInfoSerializer;
use Exception;

class EmployeeBankAccountInfoService {
    private $common_service;

    public function __construct() {
        $this->common_service = new CommonService();
    }

    public function get_bank_account_info_by_id($id) {
        try {
            $entity = DbHelper::findById(EmployeeBackAccountInfo::class, $id);
            if (!$entity) {
                throw new Exception("Bank account info not found");
            }

            $dto = new EmployeeBackAccountInfoSerializer();
            $dto->id = $entity->id;
            $dto->accountType = $entity->accountType;
            $dto->ifscCode = $entity->ifscCode;
            $dto->bankName = $entity->bankName;
            $dto->branch = $entity->branch;
            $dto->accountNumber = $entity->accountNumber;
            $dto->address = $entity->address;
            $dto->employeeId = $entity->companyEmployee;
            $dto->passbookImage = $entity->passbookImage;
            $dto->is_cash = $entity->is_cash == 1;
            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_bank_account_info() {
        try {
            $entities = DbHelper::findAll(EmployeeBackAccountInfo::class, "1=1", [], "id ASC");
            $dto_list = [];
            foreach ($entities as $entity) {
                $dto_list[] = $this->get_bank_account_info_by_id($entity->id);
            }
            return $dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_bank_account_info($dto) {
        try {
            $employee_id = $dto['employeeId'] ?? null;        
            if ($employee_id) {
                $employee = DbHelper::findById(CompanyEmployee::class, $employee_id);                
                if (!$employee) {
                    throw new Exception("Employee not found");
                }
                $entity = new EmployeeBackAccountInfo();
                $entity->companyEmployee = $employee_id;
                $entity->accountType = $dto['accountType'] ?? null;
                $entity->ifscCode = $dto['ifscCode'] ?? null;
                $entity->bankName = $dto['bankName'] ?? null;
                $entity->branch = $dto['branch'] ?? null;
                $entity->accountNumber = $dto['accountNumber'] ?? null;
                $entity->address = $dto['address'] ?? null;
                $entity->passbookImage = $dto['passbookImage'] ?? "";
                $entity->is_cash = $dto['is_cash'] ?? false;                
                $entity = DbHelper::insert($entity);
                return $this->get_bank_account_info_by_id($entity->id);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_bank_account_info($id, $dto) {
        try {
            $entity = DbHelper::findById(EmployeeBackAccountInfo::class, $id);
            if (!$entity) {
                throw new Exception("Bank account info not found");
            }

            $employee_id = $dto['employeeId'] ?? null;
            $employee = DbHelper::findById(CompanyEmployee::class, $employee_id);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $entity->companyEmployee = $employee_id;
            $entity->accountType = $dto['accountType'] ?? null;
            $entity->ifscCode = $dto['ifscCode'] ?? null;
            $entity->bankName = $dto['bankName'] ?? null;
            $entity->branch = $dto['branch'] ?? null;
            $entity->accountNumber = $dto['accountNumber'] ?? null;
            $entity->address = $dto['address'] ?? null;
            if (isset($dto['passbookImage'])) {
                $entity->passbookImage = $dto['passbookImage'];
            }
            $entity->is_cash = $dto['is_cash'] ?? false;

            DbHelper::update($entity);
            return $this->get_bank_account_info_by_id($entity->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_bank_account_info($id) {
        try {
            $entity = DbHelper::findById(EmployeeBackAccountInfo::class, $id);
            if (!$entity) {
                throw new Exception("Bank account info not found");
            }
            DbHelper::delete(EmployeeBackAccountInfo::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function upload_passbook_image($company_id, $id, $image_path) {
        try {
            $this->delete_passbook_image($company_id, $id);
            $entity = DbHelper::findById(EmployeeBackAccountInfo::class, $id);
            if (!$entity) {
                throw new Exception("Bank account info not found");
            }

            $updated_path = $this->common_service->update_file_location_for_profile(
                $image_path,
                $company_id,
                "employeeProfile/bank/" . $id
            );

            if ($updated_path === "Error") {
                return "Error";
            } else {
                $entity->passbookImage = $updated_path;
                DbHelper::update($entity);
                return $updated_path;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_passbook_image($company_id, $id) {
        try {
            $entity = DbHelper::findById(EmployeeBackAccountInfo::class, $id);
            if (!$entity) {
                throw new Exception("Bank account info not found");
            }

            $config = require __DIR__ . '/../../config/settings.php';
            $file_dir = $config['timesheetpro_drive'] ?? '';

            $existing_image_path = $file_dir . DIRECTORY_SEPARATOR . $company_id . DIRECTORY_SEPARATOR . "employeeProfile" . DIRECTORY_SEPARATOR . "bank" . DIRECTORY_SEPARATOR . $id;
            if (file_exists($existing_image_path)) {
                $this->common_service->delete_directory_recursively($existing_image_path);
            }
            $entity->passbookImage = "";
            DbHelper::update($entity);
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
