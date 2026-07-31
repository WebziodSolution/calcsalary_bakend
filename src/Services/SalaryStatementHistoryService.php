<?php
namespace Common\Services;

use Common\Models\SalaryStatementHistory;
use Common\Models\CompanyDetails;
use Common\Models\CompanyEmployee;
use Common\Models\UserInOut;
use Common\Models\EmployeeLeaveMaster;
use Common\Models\Deductions;
use Common\Services\CommonService;
use Common\Services\SalaryStatementMasterService;
use Common\Services\DbHelper;
use DateTime;
use DateTimeZone;
use Exception;

class SalaryStatementHistoryService {
    private $common_service;

    public function __construct() {
        $this->common_service = new CommonService();
    }

    private function _parse_month_year($month_year_str) {
        if (!$month_year_str) {
            return new DateTime("1970-01-01 00:00:00");
        }
        $dt = DateTime::createFromFormat("F-Y", trim($month_year_str));
        return $dt ? $dt : new DateTime("1970-01-01 00:00:00");
    }

    private function _to_dto(SalaryStatementHistory $entity) {
        $elm = DbHelper::findFirst(EmployeeLeaveMaster::class, "employee_id = :emp_id", ["emp_id" => $entity->employeeId]);
        $used_leave = $elm ? ($elm->usedLeave !== null ? (int)$elm->usedLeave : null) : null;

        return [
            "id" => $entity->id,
            "clockInOutId" => $entity->clockInOutId !== null ? (int)$entity->clockInOutId : null,
            "companyId" => $entity->companyDetails !== null ? (int)$entity->companyDetails : null,
            "employeeId" => $entity->employeeId !== null ? (int)$entity->employeeId : null,
            "employeeName" => $entity->employeeName,
            "departmentId" => $entity->departmentId !== null ? (int)$entity->departmentId : null,
            "departmentName" => $entity->departmentName,
            "basicSalary" => $entity->basicSalary !== null ? (float)$entity->basicSalary : null,
            "totalEarnSalary" => $entity->totalEarnSalary !== null ? (float)$entity->totalEarnSalary : null,
            "otAmount" => $entity->otAmount !== null ? (float)$entity->otAmount : null,
            "pfAmount" => $entity->pfAmount !== null ? (float)$entity->pfAmount : null,
            "totalPfAmount" => $entity->totalPfAmount !== null ? (float)$entity->totalPfAmount : null,
            "pfPercentage" => $entity->pfPercentage !== null ? (float)$entity->pfPercentage : null,
            "ptAmount" => $entity->ptAmount !== null ? (float)$entity->ptAmount : null,
            "totalEarnings" => $entity->totalEarnings !== null ? (float)$entity->totalEarnings : null,
            "totalPenaltyAmount" => $entity->totalPenaltyAmount !== null ? (float)$entity->totalPenaltyAmount : null,
            "otherDeductions" => $entity->otherDeductions !== null ? (float)$entity->otherDeductions : null,
            "totalDeductions" => $entity->totalDeductions !== null ? (float)$entity->totalDeductions : null,
            "netSalary" => $entity->netSalary !== null ? (float)$entity->netSalary : null,
            "year" => $entity->year !== null ? (int)$entity->year : null,
            "monthNumber" => $entity->month !== null ? (int)$entity->month : null,
            "monthYear" => $entity->monthYear,
            "totalPaidDays" => $entity->totalPaidDays !== null ? (int)$entity->totalPaidDays : null,
            "totalWorkingDays" => $entity->totalWorkingDays !== null ? (int)$entity->totalWorkingDays : null,
            "totalWorkingHours" => $entity->totalWorkingHours !== null ? (float)$entity->totalWorkingHours : null,
            "totalDays" => $entity->totalDays !== null ? (int)$entity->totalDays : null,
            "startDate" => null,
            "endDate" => null,
            "timeZone" => null,
            "note" => $entity->note,
            "generatedBy" => $entity->companyEmployee !== null ? (int)$entity->companyEmployee : null,
            "deductionsList" => $this->calculateTotalAllowanceAndDeductions($entity->employeeId, "Deduction"),
            "allowanceList" => $this->calculateTotalAllowanceAndDeductions($entity->employeeId, "Allowance"),
            "used_leave" => $used_leave,
        ];
    }

    public function calculateTotalAllowanceAndDeductions($user_id, $type_str) {
        if (!$user_id) {
            return [];
        }
        $deductions = DbHelper::findAll(Deductions::class, "employee_id = :emp_id AND type = :type", [
            "emp_id" => $user_id,
            "type" => $type_str
        ]);
        $res = [];
        foreach ($deductions as $d) {
            $res[] = [
                "label" => $d->label,
                "amount" => $d->amount !== null ? (float)$d->amount : 0.0,
                "type" => $d->type
            ];
        }
        return $res;
    }

    private function _to_start_of_day_utc(DateTime $dt = null) {
        if (!$dt) {
            return null;
        }
        $res = clone $dt;
        $res->setTime(0, 0, 0);
        $res->setTimezone(new DateTimeZone("UTC"));
        return $res;
    }

    private function _to_end_of_day_utc(DateTime $dt = null) {
        if (!$dt) {
            return null;
        }
        $res = clone $dt;
        $res->setTime(23, 59, 59);
        $res->setTimezone(new DateTimeZone("UTC"));
        return $res;
    }

    public function filterSalaryStatementHistory($employee_ids, $department_ids, $months, $company_id) {
        try {
            if (empty($employee_ids) && empty($department_ids) && empty($months)) {
                return [];
            }

            $where = [];
            $params = [];

            if ($company_id && $company_id > 0) {
                $where[] = "company_id = :comp_id";
                $params["comp_id"] = $company_id;
            }

            if (!empty($employee_ids)) {
                $placeholders = [];
                foreach ($employee_ids as $idx => $eid) {
                    $key = "eid_" . $idx;
                    $placeholders[] = ":" . $key;
                    $params[$key] = $eid;
                }
                $where[] = "employee_id IN (" . implode(", ", $placeholders) . ")";
            }

            if (!empty($department_ids)) {
                $placeholders = [];
                foreach ($department_ids as $idx => $did) {
                    $key = "did_" . $idx;
                    $placeholders[] = ":" . $key;
                    $params[$key] = $did;
                }
                $where[] = "department_id IN (" . implode(", ", $placeholders) . ")";
            }

            if (!empty($months)) {
                $placeholders = [];
                foreach ($months as $idx => $m) {
                    $key = "m_" . $idx;
                    $placeholders[] = ":" . $key;
                    $params[$key] = $m;
                }
                $where[] = "salary_month_and_year IN (" . implode(", ", $placeholders) . ")";
            }

            $entities = DbHelper::findAll(SalaryStatementHistory::class, implode(" AND ", $where), $params);
            $dto_list = [];
            foreach ($entities as $entity) {
                $dto_list[] = $this->getSalaryStatementHistory($entity->id);
            }

            $grouped = [];
            foreach ($dto_list as $dto) {
                $m_y = $dto["monthYear"] ?? "";
                if (!isset($grouped[$m_y])) {
                    $grouped[$m_y] = [];
                }
                $grouped[$m_y][] = $dto;
            }

            $sorted_keys = array_keys($grouped);
            usort($sorted_keys, function($a, $b) {
                $a_dt = $this->_parse_month_year($a);
                $b_dt = $this->_parse_month_year($b);
                return $a_dt <=> $b_dt;
            });

            $result = [];
            foreach ($sorted_keys as $k) {
                $result[] = [
                    "month" => $k,
                    "data" => $grouped[$k]
                ];
            }

            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getSalaryStatementHistory($id) {
        try {
            $entity = DbHelper::findById(SalaryStatementHistory::class, $id);
            if (!$entity) {
                throw new Exception("Salary Statement History not found");
            }
            return $this->_to_dto($entity);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function addSalaryStatement($salary_statement_list) {
        try {
            $add_val = function($val1, $val2) {
                return ($val1 !== null ? (float)$val1 : 0) + ($val2 !== null ? (float)$val2 : 0);
            };

            foreach ($salary_statement_list as $dto) {
                $start_date_raw = isset($dto["startDate"]) ? $this->common_service->convert_string_to_date($dto["startDate"]) : null;
                $end_date_raw = isset($dto["endDate"]) ? $this->common_service->convert_string_to_date($dto["endDate"]) : null;

                $start_date = $this->_to_start_of_day_utc($start_date_raw);
                $end_date = $this->_to_end_of_day_utc($end_date_raw);

                if ($start_date && $end_date) {
                    $user_in_outs = DbHelper::findAll(UserInOut::class, "user_id = :emp_id AND created_on >= :start AND created_on <= :end", [
                        "emp_id" => $dto["employeeId"] ?? null,
                        "start" => $start_date->format('Y-m-d H:i:s'),
                        "end" => $end_date->format('Y-m-d H:i:s')
                    ]);
                    foreach ($user_in_outs as $uio) {
                        $uio->isSalaryGenerate = 1;
                        DbHelper::update($uio);
                    }
                }

                $used_leave_val = $dto["used_leave"] ?? null;
                if ($used_leave_val !== null) {
                    $elm = DbHelper::findFirst(EmployeeLeaveMaster::class, "employee_id = :emp_id", [
                        "emp_id" => $dto["employeeId"] ?? null
                    ]);
                    if ($elm) {
                        $elm->usedLeave = $used_leave_val;
                        DbHelper::update($elm);
                    }
                }

                $entity = DbHelper::findFirst(SalaryStatementHistory::class, "employee_id = :emp_id AND company_id = :comp_id AND salary_month = :month AND salary_year = :year", [
                    "emp_id" => $dto["employeeId"] ?? null,
                    "comp_id" => $dto["companyId"] ?? null,
                    "month" => $dto["monthNumber"] ?? null,
                    "year" => $dto["year"] ?? null
                ]);

                if ($entity) {
                    $entity->otAmount = $add_val($dto["otAmount"] ?? 0, $entity->otAmount);
                    $entity->totalEarnSalary = $add_val($dto["totalEarnings"] ?? 0, $entity->totalEarnSalary);
                    $entity->totalPfAmount = $add_val($dto["totalPfAmount"] ?? 0, $entity->totalPfAmount);
                    $entity->ptAmount = $add_val($dto["ptAmount"] ?? 0, $entity->ptAmount);
                    $entity->netSalary = $add_val($dto["netSalary"] ?? 0, $entity->netSalary);
                    $entity->otherDeductions = $add_val($dto["otherDeductions"] ?? 0, $entity->otherDeductions);
                    $entity->totalDeductions = $add_val($dto["totalDeductions"] ?? 0, $entity->totalDeductions);
                    $entity->totalEarnings = $add_val($dto["totalEarnings"] ?? 0, $entity->totalEarnings);
                    $entity->totalPenaltyAmount = $add_val($dto["totalPenaltyAmount"] ?? 0, $entity->totalPenaltyAmount);
                    $entity->note = $dto["note"] ?? null;
                    DbHelper::update($entity);
                } else {
                    $entity = new SalaryStatementHistory();
                    $company_details = DbHelper::findById(CompanyDetails::class, $dto["companyId"]);
                    if (!$company_details) {
                        throw new Exception("Company not found");
                    }
                    $entity->companyDetails = $company_details->id;

                    $company_employee = DbHelper::findById(CompanyEmployee::class, $dto["generatedBy"]);
                    if (!$company_employee) {
                        throw new Exception("Company Employee not found");
                    }
                    $entity->companyEmployee = $company_employee->employeeId;

                    $entity->clockInOutId = isset($dto["clockInOutId"]) ? (int)$dto["clockInOutId"] : null;
                    $entity->month = isset($dto["monthNumber"]) ? (int)$dto["monthNumber"] : null;
                    $entity->year = isset($dto["year"]) ? (int)$dto["year"] : null;
                    $entity->generatedDate = new DateTime("now");

                    $entity->employeeId = isset($dto["employeeId"]) ? (int)$dto["employeeId"] : null;
                    $entity->employeeName = $dto["employeeName"] ?? null;
                    $entity->departmentId = isset($dto["departmentId"]) ? (int)$dto["departmentId"] : null;
                    $entity->departmentName = $dto["departmentName"] ?? null;
                    $entity->basicSalary = isset($dto["basicSalary"]) ? (float)$dto["basicSalary"] : null;
                    $entity->totalEarnSalary = isset($dto["totalEarnSalary"]) ? (float)$dto["totalEarnSalary"] : null;
                    $entity->otAmount = isset($dto["otAmount"]) ? (float)$dto["otAmount"] : null;
                    $entity->pfAmount = isset($dto["pfAmount"]) ? (float)$dto["pfAmount"] : null;
                    $entity->totalPfAmount = isset($dto["totalPfAmount"]) ? (float)$dto["totalPfAmount"] : null;
                    $entity->pfPercentage = isset($dto["pfPercentage"]) ? (float)$dto["pfPercentage"] : null;
                    $entity->ptAmount = isset($dto["ptAmount"]) ? (float)$dto["ptAmount"] : null;
                    $entity->totalEarnings = isset($dto["totalEarnings"]) ? (float)$dto["totalEarnings"] : null;
                    $entity->totalPenaltyAmount = isset($dto["totalPenaltyAmount"]) ? (float)$dto["totalPenaltyAmount"] : null;
                    $entity->otherDeductions = isset($dto["otherDeductions"]) ? (float)$dto["otherDeductions"] : null;
                    $entity->totalDeductions = isset($dto["totalDeductions"]) ? (float)$dto["totalDeductions"] : null;
                    $entity->netSalary = isset($dto["netSalary"]) ? (float)$dto["netSalary"] : null;
                    $entity->monthYear = $dto["monthYear"] ?? null;
                    $entity->totalPaidDays = isset($dto["totalPaidDays"]) ? (int)$dto["totalPaidDays"] : null;
                    $entity->totalWorkingDays = isset($dto["totalWorkingDays"]) ? (int)$dto["totalWorkingDays"] : null;
                    $entity->totalWorkingHours = isset($dto["totalWorkingHours"]) ? (float)$dto["totalWorkingHours"] : null;
                    $entity->totalDays = isset($dto["totalDays"]) ? (int)$dto["totalDays"] : null;
                    $entity->note = $dto["note"] ?? null;

                    $entity = DbHelper::insert($entity);
                }

                // Update or Create SalaryStatementMaster
                $master_service = new SalaryStatementMasterService();
                $master_dto = $master_service->getSalaryStatementMastersByMonthAndYear(
                    $dto["companyId"], $dto["monthNumber"], $dto["year"]
                );

                if ($master_dto) {
                    $total_salary = $add_val($dto["netSalary"] ?? 0, $master_dto["totalSalary"]);
                    $master_dto["totalSalary"] = $total_salary;

                    $pf_amount = $add_val($dto["totalPfAmount"] ?? 0, $master_dto["totalPf"]);
                    $master_dto["totalPf"] = $pf_amount;

                    $pt_amount = $add_val($dto["ptAmount"] ?? 0, $master_dto["totalPt"]);
                    $master_dto["totalPt"] = $pt_amount;

                    $master_dto["note"] = $master_dto["note"] ?? null;

                    $master_service->updateSalaryStatementMaster($master_dto["id"], $master_dto);
                } else {
                    $first_dto = $salary_statement_list[0];
                    $new_master_dto = [
                        "companyId" => $first_dto["companyId"],
                        "month" => $first_dto["monthNumber"],
                        "year" => $first_dto["year"],
                        "note" => $dto["note"] ?? null,
                        "totalSalary" => $dto["netSalary"] ?? 0,
                        "totalPf" => $dto["totalPfAmount"] ?? 0,
                        "totalPt" => $dto["ptAmount"] ?? 0
                    ];
                    $master_service->createSalaryStatementMaster($new_master_dto);
                }
            }

            return [];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateSalaryStatement($id, $dto) {
        try {
            $entity = DbHelper::findById(SalaryStatementHistory::class, $id);
            if (!$entity) {
                throw new Exception("Salary Statement History not found");
            }

            $company_id = $dto["companyId"] ?? null;
            $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company_details) {
                throw new Exception("Company not found");
            }
            $entity->companyDetails = $company_details->id;

            $generated_by = $dto["generatedBy"] ?? null;
            $company_employee = $generated_by ? DbHelper::findById(CompanyEmployee::class, $generated_by) : null;
            $entity->companyEmployee = $company_employee ? $company_employee->employeeId : null;

            $entity->clockInOutId = isset($dto["clockInOutId"]) ? (int)$dto["clockInOutId"] : null;
            $entity->month = isset($dto["monthNumber"]) ? (int)$dto["monthNumber"] : null;
            $entity->year = isset($dto["year"]) ? (int)$dto["year"] : null;

            $entity->employeeId = isset($dto["employeeId"]) ? (int)$dto["employeeId"] : null;
            $entity->employeeName = $dto["employeeName"] ?? null;
            $entity->departmentId = isset($dto["departmentId"]) ? (int)$dto["departmentId"] : null;
            $entity->departmentName = $dto["departmentName"] ?? null;
            $entity->basicSalary = isset($dto["basicSalary"]) ? (float)$dto["basicSalary"] : null;
            $entity->totalEarnSalary = isset($dto["totalEarnSalary"]) ? (float)$dto["totalEarnSalary"] : null;
            $entity->otAmount = isset($dto["otAmount"]) ? (float)$dto["otAmount"] : null;
            $entity->pfAmount = isset($dto["pfAmount"]) ? (float)$dto["pfAmount"] : null;
            $entity->totalPfAmount = isset($dto["totalPfAmount"]) ? (float)$dto["totalPfAmount"] : null;
            $entity->pfPercentage = isset($dto["pfPercentage"]) ? (float)$dto["pfPercentage"] : null;
            $entity->ptAmount = isset($dto["ptAmount"]) ? (float)$dto["ptAmount"] : null;
            $entity->totalEarnings = isset($dto["totalEarnings"]) ? (float)$dto["totalEarnings"] : null;
            $entity->totalPenaltyAmount = isset($dto["totalPenaltyAmount"]) ? (float)$dto["totalPenaltyAmount"] : null;
            $entity->otherDeductions = isset($dto["otherDeductions"]) ? (float)$dto["otherDeductions"] : null;
            $entity->totalDeductions = isset($dto["totalDeductions"]) ? (float)$dto["totalDeductions"] : null;
            $entity->netSalary = isset($dto["netSalary"]) ? (float)$dto["netSalary"] : null;
            $entity->monthYear = $dto["monthYear"] ?? null;
            $entity->totalPaidDays = isset($dto["totalPaidDays"]) ? (int)$dto["totalPaidDays"] : null;
            $entity->totalWorkingDays = isset($dto["totalWorkingDays"]) ? (int)$dto["totalWorkingDays"] : null;
            $entity->totalWorkingHours = isset($dto["totalWorkingHours"]) ? (float)$dto["totalWorkingHours"] : null;
            $entity->totalDays = isset($dto["totalDays"]) ? (int)$dto["totalDays"] : null;
            $entity->note = $dto["note"] ?? null;

            DbHelper::update($entity);

            // Aggregate totals
            $histories = DbHelper::findAll(SalaryStatementHistory::class, "company_id = :comp_id AND salary_month = :month AND salary_year = :year", [
                "comp_id" => $dto["companyId"] ?? null,
                "month" => $dto["monthNumber"] ?? null,
                "year" => $dto["year"] ?? null
            ]);

            $totalNetSalary = 0;
            $totalPfAmount = 0;
            $totalPtAmount = 0;
            foreach ($histories as $h) {
                $totalNetSalary += $h->netSalary !== null ? (float)$h->netSalary : 0.0;
                $totalPfAmount += $h->totalPfAmount !== null ? (float)$h->totalPfAmount : 0.0;
                $totalPtAmount += $h->ptAmount !== null ? (float)$h->ptAmount : 0.0;
            }

            $master_service = new SalaryStatementMasterService();
            $master_dto = $master_service->getSalaryStatementMastersByMonthAndYear(
                $dto["companyId"], $dto["monthNumber"], $dto["year"]
            );
            if ($master_dto) {
                $master_dto["totalSalary"] = $totalNetSalary;
                $master_dto["totalPf"] = $totalPfAmount;
                $master_dto["totalPt"] = $totalPtAmount;
                $master_service->updateSalaryStatementMaster($master_dto["id"], $master_dto);
            }

            return $dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function deleteSalaryStatement($id) {
        try {
            $entity = DbHelper::findById(SalaryStatementHistory::class, $id);
            if (!$entity) {
                throw new Exception("Salary Statement History not found");
            }
            DbHelper::delete(SalaryStatementHistory::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
