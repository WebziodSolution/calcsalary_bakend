<?php
namespace Common\Services;

use Common\Models\EmployeeType;
use Common\Serializers\EmployeeTypeSerializer;
use Exception;

class EmployeeTypeService {
    
    public function get_employee_type($id) {
        try {
            $employee_type = DbHelper::findById(EmployeeType::class, $id);
            if (!$employee_type) {
                throw new Exception("Type not found");
            }

            $dto = new EmployeeTypeSerializer();
            $dto->id = $employee_type->id;
            $dto->name = $employee_type->name;

            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_employee_types() {
        try {
            $employee_types = DbHelper::findAll(EmployeeType::class, "1=1", [], "id ASC");
            $dto_list = [];
            foreach ($employee_types as $et) {
                $dto_list[] = $this->get_employee_type($et->id);
            }
            return $dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_employee_type($employee_type_dto) {
        try {
            $employee_type = new EmployeeType();
            $employee_type->name = $employee_type_dto['name'] ?? null;
            $employee_type = DbHelper::insert($employee_type);

            return $this->get_employee_type($employee_type->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_employee_type($id, $employee_type_dto) {
        try {
            $employee_type = DbHelper::findById(EmployeeType::class, $id);
            if (!$employee_type) {
                throw new Exception("Type not found");
            }

            $employee_type->name = $employee_type_dto['name'] ?? null;
            DbHelper::update($employee_type);

            return $this->get_employee_type($employee_type->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_employee_type($id) {
        try {
            $employee_type = DbHelper::findById(EmployeeType::class, $id);
            if (!$employee_type) {
                throw new Exception("Type not found");
            }
            DbHelper::delete(EmployeeType::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
