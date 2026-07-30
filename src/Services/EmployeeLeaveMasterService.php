<?php
namespace Common\Services;

use Common\Models\EmployeeLeaveMaster;
use Common\Models\CompanyEmployee;
use Common\Models\LeaveType;
use Common\Serializers\EmployeeleavemasterSerializer;
use Exception;

class EmployeeLeaveMasterService {
    
    public function get_employee_leave_master($id) {
        try {
            $elm = DbHelper::findById(EmployeeLeaveMaster::class, $id);
            if (!$elm) {
                throw new Exception("Employee leave master not found");
            }

            $dto = new EmployeeleavemasterSerializer();
            $dto->id = $elm->id;
            $dto->employeeId = $elm->companyEmployee;
            $dto->leaveTypeId = $elm->leaveType;
            $dto->totalLeave = $elm->totalLeave;
            $dto->usedLeave = $elm->usedLeave;

            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_employee_leave_masters($company_id) {
        try {
            $db = DbHelper::getDb();
            // Need a raw query or join because companyDetails is on CompanyEmployee table
            $stmt = $db->prepare("
                SELECT elm.id 
                FROM employee_leave_master elm
                INNER JOIN company_employee ce ON elm.employee_id = ce.employee_id
                WHERE ce.company_id = :comp_id
                ORDER BY elm.id ASC
            ");
            $stmt->execute(["comp_id" => $company_id]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $dto_list = [];
            foreach ($rows as $row) {
                $dto_list[] = $this->get_employee_leave_master($row['id']);
            }
            return $dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_employee_leave_masters_by_employee($employee_id) {
        try {
            $entities = DbHelper::findAll(EmployeeLeaveMaster::class, "employee_id = :emp_id", ["emp_id" => $employee_id], "id ASC");
            $dto_list = [];
            foreach ($entities as $elm) {
                $dto_list[] = $this->get_employee_leave_master($elm->id);
            }
            return $dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_employee_leave_master($dto) {
        try {
            $employee_id = $dto['employeeId'] ?? null;
            if ($employee_id !== null) {
                $employee = DbHelper::findById(CompanyEmployee::class, $employee_id);
                if (!$employee) {
                    throw new Exception("Employee not found");
                }
            }

            $leave_type_id = $dto['leaveTypeId'] ?? null;
            if ($leave_type_id !== null) {
                $leave_type = DbHelper::findById(LeaveType::class, $leave_type_id);
                if (!$leave_type) {
                    throw new Exception("LeaveType not found");
                }
            }

            $elm = new EmployeeLeaveMaster();
            $elm->companyEmployee = $employee_id;
            $elm->leaveType = $leave_type_id;
            $elm->totalLeave = $dto['totalLeave'] ?? null;
            $elm->usedLeave = $dto['usedLeave'] ?? null;

            $elm = DbHelper::insert($elm);
            return $this->get_employee_leave_master($elm->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_employee_leave_master($id, $dto) {
        try {
            $elm = DbHelper::findById(EmployeeLeaveMaster::class, $id);
            if (!$elm) {
                throw new Exception("Employee leave master not found");
            }

            $employee_id = $dto['employeeId'] ?? null;
            if ($employee_id !== null) {
                $employee = DbHelper::findById(CompanyEmployee::class, $employee_id);
                if (!$employee) {
                    throw new Exception("Employee not found");
                }
            }

            $leave_type_id = $dto['leaveTypeId'] ?? null;
            if ($leave_type_id !== null) {
                $leave_type = DbHelper::findById(LeaveType::class, $leave_type_id);
                if (!$leave_type) {
                    throw new Exception("LeaveType not found");
                }
            }

            $elm->companyEmployee = $employee_id;
            $elm->leaveType = $leave_type_id;
            $elm->totalLeave = $dto['totalLeave'] ?? null;
            $elm->usedLeave = $dto['usedLeave'] ?? null;

            DbHelper::update($elm);
            return $this->get_employee_leave_master($elm->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_employee_leave_master($id) {
        try {
            $elm = DbHelper::findById(EmployeeLeaveMaster::class, $id);
            if (!$elm) {
                throw new Exception("Employee leave master not found");
            }
            DbHelper::delete(EmployeeLeaveMaster::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
