<?php
namespace Common\Services;

use Common\Models\WeeklyOff;
use Common\Models\CompanyDetails;
use Common\Models\CompanyEmployee;
use Common\Models\EmployeeType;
use Exception;

class WeeklyOffService {

    private function _convert_model_to_dto(WeeklyOff $w) {
        $assigned_emp_ids = $this->getAssignedEmployees($w->id);
        $emp = DbHelper::findById(CompanyEmployee::class, $w->companyEmployee);
        $emp_user_name = $emp ? $emp->userName : null;

        return [
            "id" => $w->id,
            "name" => $w->name,
            "description" => $w->description,
            "isDefault" => $w->isDefault,
            "sundayAll" => (bool)$w->sundayAll,
            "sunday1st" => (bool)$w->sunday1st,
            "sunday2nd" => (bool)$w->sunday2nd,
            "sunday3rd" => (bool)$w->sunday3rd,
            "sunday4th" => (bool)$w->sunday4th,
            "sunday5th" => (bool)$w->sunday5th,
            "mondayAll" => (bool)$w->mondayAll,
            "monday1st" => (bool)$w->monday1st,
            "monday2nd" => (bool)$w->monday2nd,
            "monday3rd" => (bool)$w->monday3rd,
            "monday4th" => (bool)$w->monday4th,
            "monday5th" => (bool)$w->monday5th,
            "tuesdayAll" => (bool)$w->tuesdayAll,
            "tuesday1st" => (bool)$w->tuesday1st,
            "tuesday2nd" => (bool)$w->tuesday2nd,
            "tuesday3rd" => (bool)$w->tuesday3rd,
            "tuesday4th" => (bool)$w->tuesday4th,
            "tuesday5th" => (bool)$w->tuesday5th,
            "wednesdayAll" => (bool)$w->wednesdayAll,
            "wednesday1st" => (bool)$w->wednesday1st,
            "wednesday2nd" => (bool)$w->wednesday2nd,
            "wednesday3rd" => (bool)$w->wednesday3rd,
            "wednesday4th" => (bool)$w->wednesday4th,
            "wednesday5th" => (bool)$w->wednesday5th,
            "thursdayAll" => (bool)$w->thursdayAll,
            "thursday1st" => (bool)$w->thursday1st,
            "thursday2nd" => (bool)$w->thursday2nd,
            "thursday3rd" => (bool)$w->thursday3rd,
            "thursday4th" => (bool)$w->thursday4th,
            "thursday5th" => (bool)$w->thursday5th,
            "fridayAll" => (bool)$w->fridayAll,
            "friday1st" => (bool)$w->friday1st,
            "friday2nd" => (bool)$w->friday2nd,
            "friday3rd" => (bool)$w->friday3rd,
            "friday4th" => (bool)$w->friday4th,
            "friday5th" => (bool)$w->friday5th,
            "saturdayAll" => (bool)$w->saturdayAll,
            "saturday1st" => (bool)$w->saturday1st,
            "saturday2nd" => (bool)$w->saturday2nd,
            "saturday3rd" => (bool)$w->saturday3rd,
            "saturday4th" => (bool)$w->saturday4th,
            "saturday5th" => (bool)$w->saturday5th,
            "companyId" => $w->companyDetails,
            "createdBy" => $w->companyEmployee,
            "createdByUsername" => $emp_user_name,
            "assignedEmployeeIds" => $assigned_emp_ids
        ];
    }

    public function assignEmployees($employee_ids, $weekly_off_id, $remove_employee_ids) {
        $db = DbHelper::getDb();
        try {
            $db->beginTransaction();
            if ($employee_ids) {
                $weekly_off = DbHelper::findById(WeeklyOff::class, $weekly_off_id);
                if (!$weekly_off) {
                    throw new Exception("Weekly off not found");
                }
                foreach ($employee_ids as $emp_id) {
                    $emp = DbHelper::findById(CompanyEmployee::class, $emp_id);
                    if (!$emp) {
                        throw new Exception("Employee not found with ID: " . $emp_id);
                    }
                    $emp->weeklyOff = $weekly_off_id;
                    DbHelper::update($emp);
                }
            }
            if ($remove_employee_ids) {
                foreach ($remove_employee_ids as $emp_id) {
                    $emp = DbHelper::findById(CompanyEmployee::class, $emp_id);
                    if (!$emp) {
                        throw new Exception("Employee not found with ID: " . $emp_id);
                    }
                    $emp->weeklyOff = null;
                    DbHelper::update($emp);
                }
            }
            $db->commit();
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw new Exception($e->getMessage());
        }
    }

    public function getAllByCompany($company_id) {
        try {
            $weekly_offs = DbHelper::findAll(WeeklyOff::class, "company_id = :comp_id", ["comp_id" => $company_id], "id ASC");
            $response = [];
            foreach ($weekly_offs as $w) {
                $response[] = $this->getById($w->id);
            }
            return $response;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getById($id) {
        try {
            $w = DbHelper::findById(WeeklyOff::class, $id);
            if (!$w) {
                throw new Exception("Weekly off not found");
            }
            return $this->_convert_model_to_dto($w);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function _has_any_flag($dto) {
        $flags = [
            "sundayAll", "sunday1st", "sunday2nd", "sunday3rd", "sunday4th", "sunday5th",
            "mondayAll", "monday1st", "monday2nd", "monday3rd", "monday4th", "monday5th",
            "tuesdayAll", "tuesday1st", "tuesday2nd", "tuesday3rd", "tuesday4th", "tuesday5th",
            "wednesdayAll", "wednesday1st", "wednesday2nd", "wednesday3rd", "wednesday4th", "wednesday5th",
            "thursdayAll", "thursday1st", "thursday2nd", "thursday3rd", "thursday4th", "thursday5th",
            "fridayAll", "friday1st", "friday2nd", "friday3rd", "friday4th", "friday5th",
            "saturdayAll", "saturday1st", "saturday2nd", "saturday3rd", "saturday4th", "saturday5th"
        ];
        foreach ($flags as $flag) {
            if (isset($dto[$flag]) && ($dto[$flag] === true || $dto[$flag] === 1 || $dto[$flag] === "1")) {
                return true;
            }
        }
        return false;
    }

    public function create($dto) {
        try {
            if (!$this->_has_any_flag($dto)) {
                throw new Exception("At least one weekly off must be selected");
            }

            $company_id = $dto['companyId'] ?? null;
            $name = $dto['name'] ?? null;

            $is_exists = DbHelper::findAll(WeeklyOff::class, "company_id = :comp_id AND name = :name", ["comp_id" => $company_id, "name" => $name]);
            if (!empty($is_exists)) {
                throw new Exception("Template name already exists");
            }

            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            $created_by = $dto['createdBy'] ?? null;
            $employee = DbHelper::findById(CompanyEmployee::class, $created_by);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $weekly_off = new WeeklyOff();
            $weekly_off->companyDetails = $company_id;
            $weekly_off->name = $name;
            $weekly_off->description = $dto['description'];
            $weekly_off->companyEmployee = $created_by;
            $weekly_off->isDefault = 0;

            $skipFields = [
                "id",
                "isDefault",
                "companyId",
                "createdBy",
                "createdByUsername",
                "assignedEmployeeIds",
                "name",
                "description"
            ];

            foreach ($dto as $key => $val) {
                if (property_exists($weekly_off, $key) && !in_array($key, $skipFields)) {
                    $weekly_off->$key = $val !== null ? (int)$val : 0;
                }
            }

            $weekly_off = DbHelper::insert($weekly_off);
            $dto["id"] = $weekly_off->id;
            return $dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update($id, $dto) {
        try {
            $name = $dto['name'] ?? null;
            $company_id = $dto['companyId'] ?? null;

            $is_exists = DbHelper::findAll(WeeklyOff::class, "name = :name AND company_id = :comp_id AND id != :id", ["name" => $name, "comp_id" => $company_id, "id" => $id]);
            if (!empty($is_exists)) {
                throw new Exception("Template name already exists");
            }

            if (!$this->_has_any_flag($dto)) {
                throw new Exception("At least one weekly off must be selected");
            }

            $weekly_off = DbHelper::findById(WeeklyOff::class, $id);
            if (!$weekly_off) {
                throw new Exception("Weekly off not found");
            }

            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            $created_by = $dto['createdBy'] ?? null;
            $employee = DbHelper::findById(CompanyEmployee::class, $created_by);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $weekly_off->companyDetails = $company_id;
            $weekly_off->companyEmployee = $created_by;
            $weekly_off->name = $name;
            $weekly_off->description = $dto['description'] ?? null;

            foreach ($dto as $key => $val) {
                if (property_exists($weekly_off, $key) && !in_array($key, ["id", "isDefault", "companyId", "createdBy", "createdByUsername", "assignedEmployeeIds", "name", "description"])) {
                    $weekly_off->$key = $val !== null ? (int)$val : 0;
                }
            }

            DbHelper::update($weekly_off);
            $dto["id"] = $weekly_off->id;
            return $dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete($id) {
        try {
            $weekly_off = DbHelper::findById(WeeklyOff::class, $id);
            if (!$weekly_off) {
                throw new Exception("Weekly off not found");
            }
            DbHelper::delete(WeeklyOff::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function assignDefaultWeeklyOff($id) {
        $db = DbHelper::getDb();
        try {
            $db->beginTransaction();
            $weekly_off = DbHelper::findById(WeeklyOff::class, $id);
            if (!$weekly_off) {
                throw new Exception("Weekly off not found");
            }

            $weekly_off->isDefault = ($weekly_off->isDefault == 1) ? 0 : 1;

            $default_weekly_offs = DbHelper::findAll(WeeklyOff::class, "is_default = 1 AND id != :id", ["id" => $id]);
            foreach ($default_weekly_offs as $default_weekly_off) {
                $default_weekly_off->isDefault = 0;
                DbHelper::update($default_weekly_off);
            }

            DbHelper::update($weekly_off);

            $company_employees = DbHelper::findAll(CompanyEmployee::class, "company_id = :comp_id", ["comp_id" => $weekly_off->companyDetails]);
            foreach ($company_employees as $emp) {
                $emp->weeklyOff = $weekly_off->id;
                DbHelper::update($emp);
            }
            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw new Exception($e->getMessage());
        }
    }

    public function getAssignedEmployees($weekly_off_id) {
        try {
            $employees = DbHelper::findAll(CompanyEmployee::class, "weekly_off = :weekly_off_id", ["weekly_off_id" => $weekly_off_id]);
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
