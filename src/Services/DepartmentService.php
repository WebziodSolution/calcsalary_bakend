<?php
namespace Common\Services;

use Common\Models\Department;
use Common\Models\CompanyDetails;
use Common\Serializers\DepartmentSerializer;
use Exception;

class DepartmentService {
    
    public function get_department($id) {
        try {
            $dept = DbHelper::findById(Department::class, $id);
            if (!$dept) {
                throw new Exception("Department not found");
            }

            $dto = new DepartmentSerializer();
            $dto->id = $dept->id;
            $dto->companyId = $dept->companyDetails;
            $dto->departmentName = $dept->departmentName;

            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_departments($company_id) {
        try {
            $departments = DbHelper::findAll(Department::class, "company_id = :comp_id", ["comp_id" => $company_id], "id ASC");
            $dept_dto_list = [];
            foreach ($departments as $d) {
                $dept_dto_list[] = $this->get_department($d->id);
            }
            return $dept_dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_department($department_dto) {
        try {
            $company_id = $department_dto['companyId'] ?? null;
            $company_details = null;
            if ($company_id !== null) {
                $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
                if (!$company_details) {
                    throw new Exception("Company not found");
                }
            }

            $dept = new Department();
            $dept->departmentName = $department_dto['departmentName'] ?? null;
            $dept->companyDetails = $company_id;
            $dept = DbHelper::insert($dept);

            return $this->get_department($dept->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_department($id, $department_dto) {
        try {
            $dept = DbHelper::findById(Department::class, $id);
            if (!$dept) {
                throw new Exception("Department not found");
            }

            $company_id = $department_dto['companyId'] ?? null;
            if ($company_id !== null) {
                $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
                if (!$company_details) {
                    throw new Exception("Company not found");
                }
            }

            $dept->departmentName = $department_dto['departmentName'] ?? null;
            $dept->companyDetails = $company_id;
            DbHelper::update($dept);

            return $this->get_department($dept->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_department($id) {
        try {
            $dept = DbHelper::findById(Department::class, $id);
            if (!$dept) {
                throw new Exception("Department not found");
            }
            DbHelper::delete(Department::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
