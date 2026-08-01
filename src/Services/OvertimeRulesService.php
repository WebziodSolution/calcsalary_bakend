<?php
namespace Common\Services;

use Common\Models\OvertimeRules;
use Common\Models\CompanyDetails;
use Common\Models\CompanyEmployee;
use Exception;

class OvertimeRulesService {

    private function _convert_model_to_dto(OvertimeRules $rule) {
        $employee = null;
        if ($rule->companyEmployee) {
            $employee = DbHelper::findById(CompanyEmployee::class, $rule->companyEmployee);
        }

        return [
            "id" => (int)$rule->id,
            "ruleName" => $rule->ruleName,
            "otMinutes" => (int)$rule->otMinutes,
            "otAmount" => (float)$rule->otAmount,
            "otType" => $rule->otType,
            "companyId" => (int)$rule->companyDetails,
            "createdBy" => (int)$rule->companyEmployee,
            "createdByUserName" => $employee ? $employee->userName : null
        ];
    }

    public function get_all_overtime_rules($company_id) {
        try {
            $rules_list = DbHelper::findAll(OvertimeRules::class, "company_id = :comp_id", ["comp_id" => $company_id], "id ASC");
            $dto_list = [];
            foreach ($rules_list as $rule) {
                $dto_list[] = $this->_convert_model_to_dto($rule);
            }
            return $dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_overtime_rule($id) {
        try {
            $rule = DbHelper::findById(OvertimeRules::class, $id);
            if (!$rule) {
                throw new Exception("Overtime rule not found with id: " . $id);
            }
            return $this->_convert_model_to_dto($rule);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function _find_duplicate_rule_name($id_val, $rule_name, $company_id) {
        if ($id_val === null) {
            $is_exists = DbHelper::findAll(OvertimeRules::class, "rule_name = :rule_name AND company_id = :comp_id", ["rule_name" => $rule_name, "comp_id" => $company_id]);
            return !empty($is_exists);
        } else {
            $is_exists = DbHelper::findAll(OvertimeRules::class, "rule_name = :rule_name AND company_id = :comp_id AND id != :id", ["rule_name" => $rule_name, "comp_id" => $company_id, "id" => $id_val]);
            return !empty($is_exists);
        }
    }

    public function create_overtime_rule($dto, $company_id) {
        try {
            $rule_name = $dto['ruleName'] ?? null;
            if ($this->_find_duplicate_rule_name(null, $rule_name, $company_id)) {
                throw new Exception("Overtime rule with name '{$rule_name}' already exists.");
            }

            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found with id: " . $company_id);
            }

            $created_by = $dto['createdBy'] ?? null;
            $employee = DbHelper::findById(CompanyEmployee::class, $created_by);
            if (!$employee) {
                throw new Exception("Company employee not found");
            }

            $rule = new OvertimeRules();
            $rule->companyDetails = $company_id;
            $rule->companyEmployee = $created_by;
            $rule->ruleName = $rule_name;
            $rule->otMinutes = $dto['otMinutes'] ?? null;
            $rule->otAmount = $dto['otAmount'] ?? null;
            $rule->otType = $dto['otType'] ?? null;

            $rule = DbHelper::insert($rule);
            return $this->_convert_model_to_dto($rule);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_overtime_rule($id, $dto) {
        try {
            $company_id = $dto['companyId'] ?? null;
            $rule_name = $dto['ruleName'] ?? null;
            if ($this->_find_duplicate_rule_name($id, $rule_name, $company_id)) {
                throw new Exception("Overtime rule with name '{$rule_name}' already exists.");
            }

            $rule = DbHelper::findById(OvertimeRules::class, $id);
            if (!$rule) {
                throw new Exception("Overtime rule not found");
            }

            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            $created_by = $dto['createdBy'] ?? null;
            $employee = DbHelper::findById(CompanyEmployee::class, $created_by);
            if (!$employee) {
                throw new Exception("Company employee not found");
            }

            $rule->companyDetails = $company_id;
            $rule->companyEmployee = $created_by;
            $rule->ruleName = $rule_name;
            $rule->otMinutes = $dto['otMinutes'] ?? null;
            $rule->otAmount = $dto['otAmount'] ?? null;
            $rule->otType = $dto['otType'] ?? null;
            DbHelper::update($rule);

            return $this->_convert_model_to_dto($rule);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_overtime_rule($id) {
        try {
            $rule = DbHelper::findById(OvertimeRules::class, $id);
            if (!$rule) {
                throw new Exception("Overtime rule not found");
            }
            DbHelper::delete(OvertimeRules::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
