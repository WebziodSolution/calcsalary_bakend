<?php
namespace Common\Services;

use Common\Models\HolidayTemplates;
use Common\Models\CompanyDetails;
use Common\Models\CompanyEmployee;
use Exception;

class HolidayTemplatesService {
    private $details_service;

    public function __construct() {
        $this->details_service = new HolidayTemplateDetailsService();
    }

    public function get_holiday_template_by_id($id) {
        try {
            $entity = DbHelper::findById(HolidayTemplates::class, $id);
            if (!$entity) {
                throw new Exception("Holiday Template not found");
            }

            $details_list = $this->details_service->get_all_holiday_template_details_by_template_id($entity->id);
            $assigned_emp_ids = $this->get_assigned_employees($entity->id);

            $employee = null;
            if ($entity->companyEmployee) {
                $employee = DbHelper::findById(CompanyEmployee::class, $entity->companyEmployee);
            }

            return [
                "id" => $entity->id,
                "name" => $entity->name,
                "companyId" => $entity->companyDetails,
                "createdBy" => $entity->companyEmployee,
                "createdByUserName" => $employee ? $employee->userName : null,
                "holidayTemplateDetailsList" => $details_list,
                "assignedEmployeeIds" => $assigned_emp_ids
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_holiday_templates_by_company_id($company_id) {
        try {
            $entities = DbHelper::findAll(HolidayTemplates::class, "company_id = :comp_id", ["comp_id" => $company_id], "id ASC");
            $dto_list = [];
            foreach ($entities as $entity) {
                $dto_list[] = $this->get_holiday_template_by_id($entity->id);
            }
            return $dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_holiday_template($dto) {
        $db = DbHelper::getDb();
        try {
            $db->beginTransaction();
            $company_id = $dto['companyId'] ?? null;
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            $created_by = $dto['createdBy'] ?? null;
            $employee = DbHelper::findById(CompanyEmployee::class, $created_by);
            if (!$employee) {
                throw new Exception("Company Employee not found");
            }

            $entity = new HolidayTemplates();
            $entity->name = $dto['name'] ?? null;
            $entity->companyDetails = $company_id;
            $entity->companyEmployee = $created_by;

            $entity = DbHelper::insert($entity);

            $details_list = $dto['holidayTemplateDetailsList'] ?? [];
            if (!empty($details_list)) {
                foreach ($details_list as $details_dto) {
                    $details_dto['holidayTemplateId'] = $entity->id;
                    $this->details_service->create_holiday_template_details($details_dto);
                }
            } else {
                throw new Exception("Holiday list is required");
            }

            $db->commit();
            return $this->get_holiday_template_by_id($entity->id);
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function update_holiday_template($id, $dto) {
        $db = DbHelper::getDb();
        try {
            $db->beginTransaction();
            $entity = DbHelper::findById(HolidayTemplates::class, $id);
            if (!$entity) {
                throw new Exception("Holiday Template not found");
            }

            $company_id = $dto['companyId'] ?? null;
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            $created_by = $dto['createdBy'] ?? null;
            $employee = DbHelper::findById(CompanyEmployee::class, $created_by);
            if (!$employee) {
                throw new Exception("Company Employee not found");
            }

            $entity->name = $dto['name'] ?? null;
            $entity->companyDetails = $company_id;
            $entity->companyEmployee = $created_by;
            DbHelper::update($entity);

            $details_list = $dto['holidayTemplateDetailsList'] ?? [];
            if (!empty($details_list)) {
                foreach ($details_list as $details_dto) {
                    $detail_id = $details_dto['id'] ?? null;
                    $details_dto['holidayTemplateId'] = $id;
                    if ($detail_id !== null && $detail_id !== "") {
                        $this->details_service->update_holiday_template_details((int)$detail_id, $details_dto);
                    } else {
                        $this->details_service->create_holiday_template_details($details_dto);
                    }
                }
            } else {
                throw new Exception("Holiday list is required");
            }

            $db->commit();
            return $this->get_holiday_template_by_id($entity->id);
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function delete_holiday_template($id) {
        try {
            $entity = DbHelper::findById(HolidayTemplates::class, $id);
            if (!$entity) {
                throw new Exception("Holiday Template not found");
            }
            DbHelper::delete(HolidayTemplates::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function assign_employees($template_id, $employee_ids, $remove_employee_ids) {
        $db = DbHelper::getDb();
        try {
            $db->beginTransaction();
            $template = DbHelper::findById(HolidayTemplates::class, $template_id);
            if (!$template) {
                throw new Exception("Holiday Template not found");
            }

            if ($employee_ids) {
                foreach ($employee_ids as $emp_id) {
                    $employee = DbHelper::findById(CompanyEmployee::class, $emp_id);
                    if (!$employee) {
                        throw new Exception("Company Employee not found");
                    }
                    $employee->holidayTemplates = $template_id;
                    DbHelper::update($employee);
                }
            }

            if ($remove_employee_ids) {
                foreach ($remove_employee_ids as $emp_id) {
                    $employee = DbHelper::findById(CompanyEmployee::class, $emp_id);
                    if (!$employee) {
                        throw new Exception("Company Employee not found");
                    }
                    $employee->holidayTemplates = null;
                    DbHelper::update($employee);
                }
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    public function get_assigned_employees($template_id) {
        try {
            $employees = DbHelper::findAll(CompanyEmployee::class, "holiday_templates_id = :template_id", ["template_id" => $template_id]);
            $ids = [];
            foreach ($employees as $emp) {
                $ids[] = $emp->employeeId;
            }
            return $ids;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
