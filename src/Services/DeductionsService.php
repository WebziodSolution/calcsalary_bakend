<?php
namespace Common\Services;

use Common\Models\Deductions;
use Common\Models\CompanyEmployee;
use Common\Serializers\DeductionsSerializer;
use Exception;

class DeductionsService {
    
    public function find_by_id($id) {
        try {
            $deductions = DbHelper::findById(Deductions::class, $id);
            if (!$deductions) {
                throw new Exception("Deductions not found!");
            }

            $dto = new DeductionsSerializer();
            $dto->id = $deductions->id;
            $dto->employeeId = $deductions->companyEmployee;
            $dto->type = $deductions->type;
            $dto->label = $deductions->label;
            $dto->amount = $deductions->amount;

            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function find_by_employee_id($employee_id) {
        try {
            $deductions = DbHelper::findAll(Deductions::class, "employee_id = :emp_id", ["emp_id" => $employee_id], "id ASC");
            $deductions_dto_list = [];
            foreach ($deductions as $deduction) {
                $deductions_dto_list[] = $this->find_by_id($deduction->id);
            }
            return $deductions_dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function save_deductions($deductions_dto_list) {
        $db = DbHelper::getDb();
        try {
            $db->beginTransaction();
            if (!empty($deductions_dto_list)) {
                foreach ($deductions_dto_list as $dto) {
                    $deductions_id = $dto['id'] ?? null;
                    if ($deductions_id !== null && $deductions_id !== "") {
                        $deductions = DbHelper::findById(Deductions::class, (int)$deductions_id);
                        if (!$deductions) {
                            throw new Exception("Deductions not found!");
                        }
                    } else {
                        $deductions = new Deductions();
                    }

                    $employee_id = $dto['employeeId'] ?? null;
                    $employee = DbHelper::findById(CompanyEmployee::class, $employee_id);
                    if (!$employee) {
                        throw new Exception("Employee not found!");
                    }

                    $deductions->companyEmployee = $employee_id;
                    $deductions->type = $dto['type'] ?? null;
                    $deductions->label = $dto['label'] ?? null;
                    $deductions->amount = $dto['amount'] ?? null;

                    if ($deductions_id !== null && $deductions_id !== "") {
                        DbHelper::update($deductions);
                    } else {
                        DbHelper::insert($deductions);
                    }
                }
            }
            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw new Exception($e->getMessage());
        }
    }

    public function delete_by_id($id) {
        try {
            $deductions = DbHelper::findById(Deductions::class, $id);
            if (!$deductions) {
                throw new Exception("Deductions not found!");
            }
            DbHelper::delete(Deductions::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
