<?php
namespace Common\Services;

use Common\Models\CompanyShift;
use Common\Models\CompanyDetails;
use Common\Serializers\CompanyShiftSerializer;
use Exception;

class CompanyShiftService {
    
    public function get_shift_by_id($id) {
        try {
            $shift = DbHelper::findById(CompanyShift::class, $id);
            if (!$shift) {
                throw new Exception("Shift not found");
            }

            $dto = new CompanyShiftSerializer();
            $dto->id = $shift->id !== null ? (int)$shift->id : null;
            $dto->companyId = $shift->companyDetails !== null ? (int)$shift->companyDetails : null;
            $dto->shiftName = $shift->shiftName;
            $dto->shiftType = $shift->shiftType;
            $dto->startTime = $shift->startTime !== null ? (float)$shift->startTime : null;
            $dto->endTime = $shift->endTime !== null ? (float)$shift->endTime : null;
            $dto->hours = $shift->hours !== null ? (float)$shift->hours : null;
            $dto->totalHours = $shift->totalHours !== null ? (float)$shift->totalHours : null;

            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_shifts($company_id) {
        try {
            $shifts = DbHelper::findAll(CompanyShift::class, "company_id = :company_id", ["company_id" => $company_id], "id ASC");
            $shift_dto_list = [];
            foreach ($shifts as $shift) {
                $shift_dto_list[] = $this->get_shift_by_id($shift->id);
            }
            return $shift_dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_shift($company_shift_dto) {
        try {
            $company_id = $company_shift_dto['companyId'] ?? null;
            $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company_details) {
                throw new Exception("Company not found");
            }

            $shift = new CompanyShift();
            $shift->companyDetails = $company_id;
            $shift->shiftName = $company_shift_dto['shiftName'] ?? null;
            $shift->shiftType = $company_shift_dto['shiftType'] ?? null;
            $shift->startTime = $company_shift_dto['startTime'] ?? null;
            $shift->endTime = $company_shift_dto['endTime'] ?? null;
            $shift->hours = $company_shift_dto['hours'] ?? null;
            $shift->totalHours = $company_shift_dto['totalHours'] ?? null;

            $shift = DbHelper::insert($shift);
            return $this->get_shift_by_id($shift->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_shift($id, $company_shift_dto) {
        try {
            $shift = DbHelper::findById(CompanyShift::class, $id);
            if (!$shift) {
                throw new Exception("Shift not found");
            }

            $company_id = $company_shift_dto['companyId'] ?? null;
            $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company_details) {
                throw new Exception("Company not found");
            }

            $shift->companyDetails = $company_id;
            $shift->shiftName = $company_shift_dto['shiftName'] ?? null;
            $shift->shiftType = $company_shift_dto['shiftType'] ?? null;
            $shift->startTime = $company_shift_dto['startTime'] ?? null;
            $shift->endTime = $company_shift_dto['endTime'] ?? null;
            $shift->hours = $company_shift_dto['hours'] ?? null;
            $shift->totalHours = $company_shift_dto['totalHours'] ?? null;

            DbHelper::update($shift);
            return $this->get_shift_by_id($shift->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_shift($id) {
        try {
            $shift = DbHelper::findById(CompanyShift::class, $id);
            if (!$shift) {
                throw new Exception("Shift not found");
            }
            DbHelper::delete(CompanyShift::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
