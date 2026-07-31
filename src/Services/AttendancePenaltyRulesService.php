<?php
namespace Common\Services;

use Common\Models\AttendancePenaltyRules;
use Common\Models\CompanyDetails;
use Common\Models\CompanyEmployee;
use Exception;

class AttendancePenaltyRulesService {

    private function _convert_model_to_dto(AttendancePenaltyRules $rule) {
        $emp_user_name = null;
        $created_by = $rule->companyEmployee;
        if ($created_by) {
            $employee = DbHelper::findById(CompanyEmployee::class, $created_by);
            $emp_user_name = $employee ? $employee->userName : null;
        }

        return [
            "id" => $rule->id !== null ? (int)$rule->id : null,
            "ruleName" => $rule->ruleName,
            "companyId" => $rule->companyDetails !== null ? (int)$rule->companyDetails : null,
            "createdBy" => $rule->companyEmployee !== null ? (int)$rule->companyEmployee : null,
            "createdByUserName" => $emp_user_name,
            "minutes" => $rule->minutes !== null ? (int)$rule->minutes : null,
            "deductionType" => $rule->deductionType,
            "amount" => $rule->amount !== null ? (int)$rule->amount : null,
            "count" => $rule->count !== null ? (int)$rule->count : null,
            "isEarlyExit" => (bool)$rule->isEarlyExit
        ];
    }

    public function find_all_by_company_id($flag, $company_id) {
        try {
            $is_early_exit = ($flag == 1);
            $rules = DbHelper::findAll(AttendancePenaltyRules::class, 
                "company_id = :comp_id AND is_early_exit = :is_early", 
                ["comp_id" => $company_id, "is_early" => $is_early_exit ? 1 : 0],
                "id ASC"
            );
            $result = [];
            foreach ($rules as $rule) {
                $result[] = $this->find_by_id($rule->id);
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function find_by_id($id) {
        try {
            $rule = DbHelper::findById(AttendancePenaltyRules::class, $id);
            if (!$rule) {
                throw new Exception("Attendance penalty rule not found");
            }
            return $this->_convert_model_to_dto($rule);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create($dto) {
        try {
            $rule_name = $dto['ruleName'] ?? null;
            $company_id = $dto['companyId'] ?? null;
            $is_early_exit = isset($dto['isEarlyExit']) ? (bool)$dto['isEarlyExit'] : false;
            $minutes = $dto['minutes'] ?? null;

            // Check duplication by name
            $existing_rule = DbHelper::findFirst(AttendancePenaltyRules::class, 
                "rule_name = :rule_name AND company_id = :comp_id AND is_early_exit = :is_early",
                ["rule_name" => $rule_name, "comp_id" => $company_id, "is_early" => $is_early_exit ? 1 : 0]
            );
            if ($existing_rule) {
                throw new Exception("Penalty rule already exists with name " . $rule_name);
            }

            // Check duplication by minutes
            $existing_rule_with_minutes = DbHelper::findFirst(AttendancePenaltyRules::class,
                "minutes = :minutes AND company_id = :comp_id AND is_early_exit = :is_early",
                ["minutes" => $minutes, "comp_id" => $company_id, "is_early" => $is_early_exit ? 1 : 0]
            );
            if ($existing_rule_with_minutes) {
                throw new Exception("Penalty rule already exists for " . $minutes . " minutes");
            }

            // Find company
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            // Find employee (createdBy)
            $created_by = $dto['createdBy'] ?? null;
            $employee = DbHelper::findById(CompanyEmployee::class, $created_by);
            if (!$employee) {
                throw new Exception("Company employee not found");
            }

            // Create rule object
            $rule = new AttendancePenaltyRules();
            $rule->companyDetails = $company_id;
            $rule->companyEmployee = $created_by;
            $rule->ruleName = $rule_name;
            $rule->minutes = $minutes;
            $rule->deductionType = $dto['deductionType'] ?? null;
            $rule->amount = $dto['amount'] ?? null;
            $rule->count = $dto['count'] ?? null;
            $rule->isEarlyExit = $is_early_exit;

            $rule = DbHelper::insert($rule);

            return $this->_convert_model_to_dto($rule);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update($id, $dto) {
        try {
            $rule_name = $dto['ruleName'] ?? null;
            $company_id = $dto['companyId'] ?? null;
            $is_early_exit = isset($dto['isEarlyExit']) ? (bool)$dto['isEarlyExit'] : false;
            $minutes = $dto['minutes'] ?? null;

            // Check duplication by name excluding current ID
            $existing_rule = DbHelper::findFirst(AttendancePenaltyRules::class, 
                "rule_name = :rule_name AND company_id = :comp_id AND is_early_exit = :is_early AND id != :id",
                ["rule_name" => $rule_name, "comp_id" => $company_id, "is_early" => $is_early_exit ? 1 : 0, "id" => $id]
            );
            if ($existing_rule) {
                throw new Exception("Penalty rule already exists with name " . $rule_name);
            }

            // Check duplication by minutes excluding current ID
            $existing_rule_with_minutes = DbHelper::findFirst(AttendancePenaltyRules::class,
                "minutes = :minutes AND company_id = :comp_id AND is_early_exit = :is_early AND id != :id",
                ["minutes" => $minutes, "comp_id" => $company_id, "is_early" => $is_early_exit ? 1 : 0, "id" => $id]
            );
            if ($existing_rule_with_minutes) {
                throw new Exception("Penalty rule already exists for " . $minutes . " minutes");
            }

            // Get the penalty rule to update
            $rule = DbHelper::findById(AttendancePenaltyRules::class, $id);
            if (!$rule) {
                throw new Exception("Penalty rule not found");
            }

            // Find company
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            // Find employee (createdBy)
            $created_by = $dto['createdBy'] ?? null;
            if ($created_by) {
                $employee = DbHelper::findById(CompanyEmployee::class, $created_by);
                if (!$employee) {
                    throw new Exception("Company employee not found");
                }
                $rule->companyEmployee = $created_by;
            }

            $rule->companyDetails = $company_id;
            $rule->ruleName = $rule_name;
            $rule->minutes = $minutes;
            $rule->deductionType = $dto['deductionType'] ?? null;
            $rule->amount = $dto['amount'] ?? null;
            $rule->count = $dto['count'] ?? null;
            $rule->isEarlyExit = $is_early_exit;

            DbHelper::update($rule);

            return $this->_convert_model_to_dto($rule);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_by_id($id) {
        try {
            $rule = DbHelper::findById(AttendancePenaltyRules::class, $id);
            if (!$rule) {
                throw new Exception("Penalty rule not found");
            }
            DbHelper::delete(AttendancePenaltyRules::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
