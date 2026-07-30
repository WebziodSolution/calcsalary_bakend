<?php
namespace Common\Services;

use Common\Models\LeaveType;
use Common\Models\CompanyDetails;
use Common\Serializers\LeaveTypeSerializer;
use Exception;

class LeaveTypeService {
    
    public function get_leave_type($id) {
        try {
            $lt = DbHelper::findById(LeaveType::class, $id);
            if (!$lt) {
                throw new Exception("LeaveType not found");
            }

            $dto = new LeaveTypeSerializer();
            $dto->id = $lt->id;
            $dto->companyId = $lt->companyDetails;
            $dto->name = $lt->name;

            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_leave_types($company_id) {
        try {
            $leave_types = DbHelper::findAll(LeaveType::class, "company_id = :comp_id", ["comp_id" => $company_id], "id ASC");
            $dto_list = [];
            foreach ($leave_types as $lt) {
                $dto_list[] = $this->get_leave_type($lt->id);
            }
            return $dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_leave_type($leave_type_dto) {
        try {
            $company_id = $leave_type_dto['companyId'] ?? null;
            $company_details = null;
            if ($company_id !== null) {
                $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
                if (!$company_details) {
                    throw new Exception("Company not found");
                }
            }

            $lt = new LeaveType();
            $lt->name = $leave_type_dto['name'] ?? null;
            $lt->companyDetails = $company_id;
            $lt = DbHelper::insert($lt);

            return $this->get_leave_type($lt->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_leave_type($id, $leave_type_dto) {
        try {
            $lt = DbHelper::findById(LeaveType::class, $id);
            if (!$lt) {
                throw new Exception("LeaveType not found");
            }

            $company_id = $leave_type_dto['companyId'] ?? null;
            if ($company_id !== null) {
                $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
                if (!$company_details) {
                    throw new Exception("Company not found");
                }
            }

            $lt->name = $leave_type_dto['name'] ?? null;
            $lt->companyDetails = $company_id;
            DbHelper::update($lt);

            return $this->get_leave_type($lt->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_leave_type($id) {
        try {
            $lt = DbHelper::findById(LeaveType::class, $id);
            if (!$lt) {
                throw new Exception("LeaveType not found");
            }
            DbHelper::delete(LeaveType::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
