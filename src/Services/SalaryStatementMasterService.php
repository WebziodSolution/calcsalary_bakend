<?php
namespace Common\Services;

use Common\Models\SalaryStatementMaster;
use Common\Models\CompanyDetails;
use Common\Services\DbHelper;
use Exception;

class SalaryStatementMasterService {
    private function _to_dto(SalaryStatementMaster $entity) {
        return [
            "id" => $entity->id,
            "companyId" => $entity->companyDetails !== null ? (int)$entity->companyDetails : null,
            "month" => $entity->month !== null ? (int)$entity->month : null,
            "year" => $entity->year !== null ? (int)$entity->year : null,
            "totalSalary" => $entity->totalSalary !== null ? (float)$entity->totalSalary : null,
            "totalPf" => $entity->totalPf !== null ? (float)$entity->totalPf : null,
            "totalPt" => $entity->totalPt !== null ? (float)$entity->totalPt : null,
            "note" => $entity->note,
        ];
    }

    public function getAllSalaryStatementMasters($company_id) {
        try {
            $entities = DbHelper::findAll(SalaryStatementMaster::class, "company_id = :comp_id", ["comp_id" => $company_id]);
            $dtos = [];
            foreach ($entities as $e) {
                $dtos[] = $this->_to_dto($e);
            }
            return $dtos;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getSalaryStatementMastersByMonthAndYear($company_id, $month, $year) {
        try {
            $entity = DbHelper::findFirst(SalaryStatementMaster::class, "company_id = :comp_id AND month = :month AND year = :year", [
                "comp_id" => $company_id,
                "month" => $month,
                "year" => $year
            ]);
            if ($entity) {
                return $this->_to_dto($entity);
            }
            return null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getSalaryStatementMasterById($id) {
        try {
            $entity = DbHelper::findById(SalaryStatementMaster::class, $id);
            if (!$entity) {
                throw new Exception("Salary Statement Master not found");
            }
            return $this->_to_dto($entity);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function createSalaryStatementMaster($dto) {
        try {
            $company_id = $dto["companyId"] ?? null;
            $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company_details) {
                throw new Exception("Company not found");
            }

            $entity = new SalaryStatementMaster();
            $entity->companyDetails = $company_id;
            $entity->month = isset($dto["month"]) ? (int)$dto["month"] : null;
            $entity->year = isset($dto["year"]) ? (int)$dto["year"] : null;
            $entity->totalSalary = isset($dto["totalSalary"]) ? (float)$dto["totalSalary"] : null;
            $entity->totalPf = isset($dto["totalPf"]) ? (float)$dto["totalPf"] : null;
            $entity->totalPt = isset($dto["totalPt"]) ? (float)$dto["totalPt"] : null;
            $entity->note = $dto["note"] ?? null;

            $entity = DbHelper::insert($entity);
            return $this->_to_dto($entity);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateSalaryStatementMaster($id, $dto) {
        try {
            $entity = DbHelper::findById(SalaryStatementMaster::class, $id);
            if (!$entity) {
                throw new Exception("Salary Statement Master not found");
            }

            $company_id = $dto["companyId"] ?? null;
            $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company_details) {
                throw new Exception("Company not found");
            }

            $entity->companyDetails = $company_id;
            $entity->month = isset($dto["month"]) ? (int)$dto["month"] : null;
            $entity->year = isset($dto["year"]) ? (int)$dto["year"] : null;
            $entity->totalSalary = isset($dto["totalSalary"]) ? (float)$dto["totalSalary"] : null;
            $entity->totalPf = isset($dto["totalPf"]) ? (float)$dto["totalPf"] : null;
            $entity->totalPt = isset($dto["totalPt"]) ? (float)$dto["totalPt"] : null;
            $entity->note = $dto["note"] ?? null;

            DbHelper::update($entity);
            return $this->_to_dto($entity);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function deleteSalaryStatementMaster($id) {
        try {
            $entity = DbHelper::findById(SalaryStatementMaster::class, $id);
            if (!$entity) {
                throw new Exception("Salary Statement Master not found");
            }
            DbHelper::delete(SalaryStatementMaster::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
