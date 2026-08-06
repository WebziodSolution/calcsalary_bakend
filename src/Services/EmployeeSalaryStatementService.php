<?php
namespace Common\Services;

use Common\Models\CompanyEmployee;
use Common\Models\UserInOut;
use Common\Models\AttendancePenaltyRules;
use Common\Models\Deductions;
use Common\Models\EmployeeLeaveMaster;
use Common\Models\WeeklyOff;
use Common\Models\HolidayTemplates;
use Common\Models\HolidayTemplateDetails;
use Common\Models\CompanyShift;
use Common\Models\Department;
use Common\Models\OvertimeRules;
use Common\Services\CommonService;
use Common\Services\HolidayTemplatesService;
use Common\Services\DbHelper;
use DateTime;
use DateTimeZone;
use DateInterval;
use Exception;

class EmployeeSalaryStatementService {
    private $holiday_templates_service;
    private $common_service;

    public function __construct() {
        $this->holiday_templates_service = new HolidayTemplatesService();
        $this->common_service = new CommonService();
    }

    public function get_employee_salary_statements($request_dto) {
        try {
            $salary_statement_list = [];
            
            $employee_ids = $request_dto["employeeIds"] ?? null;
            $department_ids = $request_dto["departmentIds"] ?? null;
            $company_id = $request_dto["companyId"] ?? null;

            $has_employee_filter = $employee_ids !== null && is_array($employee_ids) && count($employee_ids) > 0;
            $has_department_filter = $department_ids !== null && is_array($department_ids) && count($department_ids) > 0;

            if (!$has_employee_filter && !$has_department_filter) {
                $company_employees = DbHelper::findAll(CompanyEmployee::class, "company_id = :comp_id", ["comp_id" => $company_id]);
            } else {
                $where = [];
                $params = [];
                if ($has_employee_filter) {
                    $placeholders = [];
                    foreach ($employee_ids as $idx => $eid) {
                        $key = "eid_" . $idx;
                        $placeholders[] = ":" . $key;
                        $params[$key] = $eid;
                    }
                    $where[] = "id IN (" . implode(", ", $placeholders) . ")";
                }
                if ($has_department_filter) {
                    $placeholders = [];
                    foreach ($department_ids as $idx => $did) {
                        $key = "did_" . $idx;
                        $placeholders[] = ":" . $key;
                        $params[$key] = $did;
                    }
                    $where[] = "department_id IN (" . implode(", ", $placeholders) . ")";
                }
                $company_employees = DbHelper::findAll(CompanyEmployee::class, implode(" AND ", $where), $params);
            }

            foreach ($company_employees as $employee) {
                $dto = $this->build_employee_salary_statement($employee, $request_dto);
                if ($dto !== null) {
                    $salary_statement_list[] = $dto;
                }
            }
            
            return $salary_statement_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function build_employee_salary_statement(CompanyEmployee $company_employee, $salary_statement_request_dto) {
        $time_zone = $salary_statement_request_dto["timeZone"] ?? "Asia/Calcutta";
        if ($time_zone === "Asia/Calcutta") {
            $time_zone = "Asia/Kolkata";
        }
        $tz = new DateTimeZone($time_zone);
        $utc_tz = new DateTimeZone("UTC");

        $start_date_str = $salary_statement_request_dto["startDate"] ?? null;
        $end_date_str = $salary_statement_request_dto["endDate"] ?? null;

        if ($start_date_str && $end_date_str) {
            // strip time part or commas if any
            if (strpos($start_date_str, ",") !== false) {
                $parts = explode(",", $start_date_str);
                $start_date_str = trim($parts[0]);
            }
            if (strpos($end_date_str, ",") !== false) {
                $parts = explode(",", $end_date_str);
                $end_date_str = trim($parts[0]);
            }
            $start_local_date = DateTime::createFromFormat("d/m/Y", trim($start_date_str));
            $end_local_date = DateTime::createFromFormat("d/m/Y", trim($end_date_str));
        } else {
            $now = new DateTime("now", $tz);
            $start_local_date = DateTime::createFromFormat("Y-m-d", $now->format("Y-m-01"));
            $end_local_date = clone $start_local_date;
            $end_local_date->modify('last day of this month');
        }

        $start_datetime_local = new DateTime($start_local_date->format("Y-m-d 00:00:00"), $tz);
        $start_date_utc = clone $start_datetime_local;
        $start_date_utc->setTimezone($utc_tz);

        $end_datetime_local = new DateTime($end_local_date->format("Y-m-d 23:59:59"), $tz);
        $end_date_utc = clone $end_datetime_local;
        $end_date_utc->setTimezone($utc_tz);

        $dto = [
            "employeeId" => $company_employee->employeeId,
            "companyId" => $company_employee->companyDetails !== null ? (int)$company_employee->companyDetails : null,
            "employeeName" => trim(($company_employee->firstName ?? "") . " " . ($company_employee->lastName ?? "")),
        ];

        if ($company_employee->basicSalary !== null) {
            $dto["basicSalary"] = (float)$company_employee->basicSalary;
        }

        if ($company_employee->department) {
            $dept = DbHelper::findById(Department::class, $company_employee->department);
            if ($dept) {
                $dto["departmentId"] = $dept->id;
                $dto["departmentName"] = $dept->departmentName;
            }
        }

        // Fetch Holiday Dates
        $holiday_dates = [];
        if ($company_employee->holidayTemplates) {
            try {
                $holiday_template = $this->holiday_templates_service->get_holiday_template_by_id($company_employee->holidayTemplates);
                if ($holiday_template && !empty($holiday_template["holidayTemplateDetailsList"])) {
                    foreach ($holiday_template["holidayTemplateDetailsList"] as $detail) {
                        $detail_date = $detail["date"] ?? null;
                        if ($detail_date) {
                            $utc_date = $this->common_service->convert_string_to_date($detail_date);
                            if ($utc_date) {
                                $utc_date->setTimezone($tz);
                                $holiday_dates[] = $utc_date->format("Y-m-d");
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // Ignore
            }
        }

        // Get Paid Day Configuration (All potential Weekly Offs + Holidays in range)
        $config_paid_off_days = [];
        if ($company_employee->weeklyOff || count($holiday_dates) > 0) {
            $config_paid_off_days = $this->calculate_paid_days(
                $start_local_date, $end_local_date, $company_employee->weeklyOff, $holiday_dates
            );
        }

        // Get actual attendance data
        $user_in_out_list = DbHelper::findAll(UserInOut::class, "user_id = :emp_id AND created_on >= :start AND created_on <= :end AND is_salary_generate = 0", [
            "emp_id" => $company_employee->employeeId,
            "start" => $start_date_utc->format('Y-m-d H:i:s'),
            "end" => $end_date_utc->format('Y-m-d H:i:s')
        ]);

        if (empty($user_in_out_list)) {
            return null;
        }

        // Process attendance records
        $daily_worked_minutes = [];
        $actual_work_days = [];

        $total_worked_millis = 0;
        $penalty_amount = 0;
        $adjusted_work_minutes_total = 0;

        $shift = null;
        if ($company_employee->companyShift) {
            $shift = DbHelper::findById(CompanyShift::class, $company_employee->companyShift);
        }

        foreach ($user_in_out_list as $user_in_out) {
            $dto["clockInOutId"] = $user_in_out->id;
            $time_in = $user_in_out->timeIn;
            $time_out = $user_in_out->timeOut;

            if ($time_in && $time_out) {
                $time_in_dt = $time_in instanceof DateTime ? $time_in : new DateTime($time_in, $utc_tz);
                $time_out_dt = $time_out instanceof DateTime ? $time_out : new DateTime($time_out, $utc_tz);

                $time_in_dt->setTimezone($tz);
                $time_out_dt->setTimezone($tz);

                $worked_seconds = $time_out_dt->getTimestamp() - $time_in_dt->getTimestamp();
                $worked_millis = $worked_seconds * 1000;
                $total_worked_millis += $worked_millis;

                $date_val = $time_in_dt->format("Y-m-d");
                $work_minutes = (int)($worked_seconds / 60);

                $lunch_break_minutes = $company_employee->lunchBreak !== null ? (int)$company_employee->lunchBreak : 0;
                $adjusted_work_minutes = max(0, $work_minutes - $lunch_break_minutes);
                $adjusted_work_minutes_total += $adjusted_work_minutes;

                $daily_worked_minutes[$date_val] = ($daily_worked_minutes[$date_val] ?? 0) + $adjusted_work_minutes;
                if (!in_array($date_val, $actual_work_days)) {
                    $actual_work_days[] = $date_val;
                }

                // Penalty Calculations
                if ($company_employee->lateEntryPenaltyRule === true || $company_employee->lateEntryPenaltyRule == 1) {
                    if ($shift && $shift->shiftType === "Time Based") {
                        $penalty_amount += $this->calculate_late_entry_penalty($company_employee, $time_in_dt, $tz);
                    }
                }

                if ($company_employee->earlyExitPenaltyRule === true || $company_employee->earlyExitPenaltyRule == 1) {
                    if ($shift && $shift->shiftType === "Time Based") {
                        $penalty_amount += $this->calculate_early_exit_penalty($company_employee, $time_out_dt, $tz);
                    }
                }
            }
        }

        // Calculate Final Paid Days
        // Remove any day the employee actually worked from the paid off-days pool.
        $config_paid_off_days = array_diff($config_paid_off_days, $actual_work_days);
        $total_paid_days_count = count($config_paid_off_days);

        // Overtime & Deductions
        $employee_shift_hours = ($shift && $shift->totalHours !== null) ? (float)$shift->totalHours : 0.0;
        $shift_minutes = $employee_shift_hours * 60.0;
        
        $ot_final_minutes = 0;
        foreach ($actual_work_days as $date_val) {
            $worked_min = $daily_worked_minutes[$date_val] ?? 0;
            $daily_ot = max(0, $worked_min - $shift_minutes);
            $ot_final_minutes += $daily_ot;
        }
        
        $ot_amount_final = 0;
        $employee_type_id = null;
        $employee_type_name = null;
        if ($company_employee->employeeType) {
            $emp_type = DbHelper::findById(CompanyEmployee::class, $company_employee->employeeType); // wait, is it employee_type table?
            // Let's query using database query to find employee type
            $db = DbHelper::getDb();
            $st = $db->prepare("SELECT * FROM employee_type WHERE id = :id");
            $st->execute(['id' => $company_employee->employeeType]);
            $et = $st->fetch(\PDO::FETCH_ASSOC);
            if ($et) {
                $employee_type_id = (int)$et['id'];
                $employee_type_name = $et['name'];
            }
        }

        if ($employee_type_id !== 2) {
            $ot_amount_final = $this->calculate_overtime_amount($company_employee, $daily_worked_minutes, $actual_work_days, $shift);
        }

        // Earnings
        $is_hourly = ($employee_type_id === 2 && $company_employee->hourlyRate !== null);

        $allowances = $this->calculate_total_allowance_and_deductions($company_employee->employeeId, "Allowance");
        $total_allowance = 0;
        foreach ($allowances as $item) {
            $total_allowance += $item["amount"];
        }

        $deductions = $this->calculate_total_allowance_and_deductions($company_employee->employeeId, "Deduction");
        $total_deduction_amount = 0;
        foreach ($deductions as $item) {
            $total_deduction_amount += $item["amount"];
        }

        if ($is_hourly) {
            $total_minutes = $adjusted_work_minutes_total;
            $hrs = (int)($total_minutes / 60);
            $mins = $total_minutes % 60;
            $worked_hours = $hrs + ($mins / 100.0);
            $hourly_rate = (float)$company_employee->hourlyRate;
            $dto["totalWorkingHours"] = $worked_hours;
            $pay_for_worked = ($hrs * $hourly_rate) + ($mins * $hourly_rate / 60.0);
            $base_salary = (int)round($pay_for_worked);
        } else {
            $monthly_salary = $company_employee->basicSalary !== null ? (float)$company_employee->basicSalary : 0.0;
            $daily_rate = $monthly_salary / 30.0;

            $paid_off_days = $this->calculate_paid_days(
                $start_local_date, $end_local_date, $company_employee->weeklyOff, $holiday_dates
            );
            
            $paid_day_count = min(count($actual_work_days) + count($paid_off_days), 30);
            $base_salary = (int)round($daily_rate * $paid_day_count);
        }

        $other_deductions = $this->calculate_canteen_deductions($company_employee, $daily_worked_minutes, $actual_work_days) + $penalty_amount;
        $total_earnings = $base_salary + $ot_amount_final + $total_allowance;

        $pt_amount = 0;
        if ($company_employee->isPt === true || $company_employee->isPt == 1) {
            $pt_amount = $company_employee->ptAmount !== null ? (int)$company_employee->ptAmount : 0;
        }
        $dto["ptAmount"] = $pt_amount;

        $total_deductions = $other_deductions + $total_deduction_amount + $pt_amount;

        // Calculate PF
        $pf_amount = 0;
        if ($company_employee->isPf === true || $company_employee->isPf == 1) {
            $earnings_for_pf = $total_earnings - $total_deductions;
            if ($earnings_for_pf >= 15000) {
                $pf_amount = 1800;
            } else {
                $pf_amount = (int)(($earnings_for_pf * 12) / 100);
            }
        }

        $dto["totalPfAmount"] = $pf_amount;
        if ($company_employee->isPf === true || $company_employee->isPf == 1) {
            if ($total_earnings - $total_deductions >= 15000) {
                $dto["pfAmount"] = 1800;
                $dto["pfPercentage"] = null;
            } else {
                $dto["pfAmount"] = null;
                $dto["pfPercentage"] = 12;
            }
        } else {
            $dto["pfAmount"] = 0;
            $dto["pfPercentage"] = null;
        }

        $total_deductions += $pf_amount;

        // Set DTO values
        $dto["totalEarnSalary"] = $base_salary;
        $dto["overTime"] = $ot_final_minutes;
        $dto["otAmount"] = $ot_amount_final;
        $dto["totalPaidDays"] = $total_paid_days_count;
        $dto["totalWorkingDays"] = count($actual_work_days);
        $dto["totalDays"] = $total_paid_days_count + count($actual_work_days);
        $dto["totalAllowance"] = $total_allowance;
        $dto["totalEarnings"] = $total_earnings;
        $dto["deduction"] = $total_deduction_amount;
        $dto["otherDeductions"] = $other_deductions;
        $dto["totalPenaltyAmount"] = $penalty_amount;
        $dto["totalDeductions"] = $total_deductions;
        $dto["netSalary"] = $total_earnings - $total_deductions;
        $dto["employeeType"] = $employee_type_name;

        // Calculate absent count
        $absent_count = 0;
        $curr = clone $start_local_date;
        $weekly_off_obj = null;
        if ($company_employee->weeklyOff) {
            $weekly_off_obj = DbHelper::findById(WeeklyOff::class, $company_employee->weeklyOff);
        }

        while ($curr <= $end_local_date) {
            $curr_str = $curr->format("Y-m-d");
            if (!in_array($curr_str, $actual_work_days)) {
                $is_holiday = in_array($curr_str, $holiday_dates);
                $is_weekly_off = false;
                if (!$is_holiday && $weekly_off_obj) {
                    $is_weekly_off = $this->is_weekly_off_day($curr, $weekly_off_obj);
                }
                
                if (!$is_holiday && !$is_weekly_off) {
                    $absent_count += 1;
                }
            }
            $curr->modify('+1 day');
        }

        $elm = DbHelper::findFirst(EmployeeLeaveMaster::class, "employee_id = :emp_id", ["emp_id" => $company_employee->employeeId]);
        if ($elm) {
            $current_used = $elm->usedLeave !== null ? (int)$elm->usedLeave : 0;
            $total_leave = $elm->totalLeave;
            $new_used = $current_used + $absent_count;
            if ($total_leave !== null && $new_used > (int)$total_leave) {
                $new_used = (int)$total_leave;
            }
            $dto["used_leave"] = $new_used;
        } else {
            $dto["used_leave"] = $absent_count;
        }

        return $dto;
    }

    public function calculate_paid_days($start_local_date, $end_local_date, $weekly_off_id, $holiday_dates) {
        $paid_days = [];
        $curr = clone $start_local_date;
        $weekly_off_obj = null;
        if ($weekly_off_id) {
            $weekly_off_obj = DbHelper::findById(WeeklyOff::class, $weekly_off_id);
        }

        while ($curr <= $end_local_date) {
            $curr_str = $curr->format("Y-m-d");
            $is_off_day = false;
            
            if (!empty($holiday_dates) && in_array($curr_str, $holiday_dates)) {
                $is_off_day = true;
            }

            if (!$is_off_day && $weekly_off_obj !== null) {
                $is_off_day = $this->is_weekly_off_day($curr, $weekly_off_obj);
            }

            if ($is_off_day) {
                $paid_days[] = $curr_str;
            }
                
            $curr->modify('+1 day');
        }
        return $paid_days;
    }

    public function is_weekly_off_day(DateTime $date_obj, WeeklyOff $config) {
        $day_of_week = (int)$date_obj->format('N'); // 1 (Mon) to 7 (Sun)
        $day_of_month = (int)$date_obj->format('j');
        $week_of_month = (int)ceil($day_of_month / 7);
        
        if ($day_of_week === 7) {  // SUNDAY
            if ($config->sundayAll) return true;
            return ($week_of_month === 1 && $config->sunday1st) ||
                   ($week_of_month === 2 && $config->sunday2nd) ||
                   ($week_of_month === 3 && $config->sunday3rd) ||
                   ($week_of_month === 4 && $config->sunday4th) ||
                   ($week_of_month === 5 && $config->sunday5th);
        } else if ($day_of_week === 1) {  // MONDAY
            if ($config->mondayAll) return true;
            return ($week_of_month === 1 && $config->monday1st) ||
                   ($week_of_month === 2 && $config->monday2nd) ||
                   ($week_of_month === 3 && $config->monday3rd) ||
                   ($week_of_month === 4 && $config->monday4th) ||
                   ($week_of_month === 5 && $config->monday5th);
        } else if ($day_of_week === 2) {  // TUESDAY
            if ($config->tuesdayAll) return true;
            return ($week_of_month === 1 && $config->tuesday1st) ||
                   ($week_of_month === 2 && $config->tuesday2nd) ||
                   ($week_of_month === 3 && $config->tuesday3rd) ||
                   ($week_of_month === 4 && $config->tuesday4th) ||
                   ($week_of_month === 5 && $config->tuesday5th);
        } else if ($day_of_week === 3) {  // WEDNESDAY
            if ($config->wednesdayAll) return true;
            return ($week_of_month === 1 && $config->wednesday1st) ||
                   ($week_of_month === 2 && $config->wednesday2nd) ||
                   ($week_of_month === 3 && $config->wednesday3rd) ||
                   ($week_of_month === 4 && $config->wednesday4th) ||
                   ($week_of_month === 5 && $config->wednesday5th);
        } else if ($day_of_week === 4) {  // THURSDAY
            if ($config->thursdayAll) return true;
            return ($week_of_month === 1 && $config->thursday1st) ||
                   ($week_of_month === 2 && $config->thursday2nd) ||
                   ($week_of_month === 3 && $config->thursday3rd) ||
                   ($week_of_month === 4 && $config->thursday4th) ||
                   ($week_of_month === 5 && $config->thursday5th);
        } else if ($day_of_week === 5) {  // FRIDAY
            if ($config->fridayAll) return true;
            return ($week_of_month === 1 && $config->friday1st) ||
                   ($week_of_month === 2 && $config->friday2nd) ||
                   ($week_of_month === 3 && $config->friday3rd) ||
                   ($week_of_month === 4 && $config->friday4th) ||
                   ($week_of_month === 5 && $config->friday5th);
        } else if ($day_of_week === 6) {  // SATURDAY
            if ($config->saturdayAll) return true;
            return ($week_of_month === 1 && $config->saturday1st) ||
                   ($week_of_month === 2 && $config->saturday2nd) ||
                   ($week_of_month === 3 && $config->saturday3rd) ||
                   ($week_of_month === 4 && $config->saturday4th) ||
                   ($week_of_month === 5 && $config->saturday5th);
        }
        return false;
    }

    public function calculate_overtime_amount(CompanyEmployee $employee, array $daily_worked_minutes, array $actual_work_days, $shift) {
        if (!$employee->overtimeRules) {
            return 0;
        }

        $rule = DbHelper::findById(OvertimeRules::class, $employee->overtimeRules);
        if (!$rule) {
            return 0;
        }

        $ot_pay_per_slab = $rule->otAmount !== null ? (float)$rule->otAmount : 0.0;
        $daily_salary = 0;

        // Fetch employee type
        $employee_type_id = null;
        if ($employee->employeeType) {
            $db = DbHelper::getDb();
            $st = $db->prepare("SELECT id FROM employee_type WHERE id = :id");
            $st->execute(['id' => $employee->employeeType]);
            $et = $st->fetch(\PDO::FETCH_ASSOC);
            if ($et) {
                $employee_type_id = (int)$et['id'];
            }
        }

        if ($employee_type_id === 2 && $employee->hourlyRate !== null) {
            $shift_hours = ($shift && $shift->totalHours !== null) ? (float)$shift->totalHours : 0.0;
            $daily_salary = (int)($shift_hours * (float)$employee->hourlyRate);
        } else {
            $basic = $employee->basicSalary !== null ? (int)$employee->basicSalary : 0;
            $daily_salary = (int)floor($basic / 30);
        }

        $ot_type = $rule->otType ? strtolower(trim($rule->otType)) : "";
        $shift_minutes = ($shift && $shift->totalHours !== null) ? (float)$shift->totalHours * 60.0 : 0.0;

        $total_ot_amount = 0;
        foreach ($actual_work_days as $date_val) {
            $worked_min = $daily_worked_minutes[$date_val] ?? 0;
            $daily_ot_minutes = max(0, $worked_min - $shift_minutes);
            if ($daily_ot_minutes <= 0) {
                continue;
            }

            if ($ot_type === "fixed amount" || $ot_type === "fixed amount per hour") {
                $ot_hours = (int)ceil($daily_ot_minutes / 60.0);
                $total_ot_amount += (int)($ot_hours * $ot_pay_per_slab);
            } else if ($ot_type === "1 day salary") {
                $total_ot_amount += $daily_salary;
            } else if ($ot_type === "1.5 day salary") {
                $total_ot_amount += (int)round($daily_salary * 1.5);
            } else if ($ot_type === "2 day salary") {
                $total_ot_amount += $daily_salary * 2;
            } else if ($ot_type === "2.5 day salary") {
                $total_ot_amount += (int)round($daily_salary * 2.5);
            } else if ($ot_type === "3 day salary") {
                $total_ot_amount += $daily_salary * 3;
            }
        }

        return $total_ot_amount;
    }

    public function calculate_canteen_deductions(CompanyEmployee $employee, $daily_worked_minutes, $work_days) {
        $canteen_type = $employee->canteenType;
        $canteen_amount = $employee->canteenAmount !== null ? (int)$employee->canteenAmount : 0;
        
        if ($canteen_type === "Office Type") {
            return $canteen_amount;
        } else if ($canteen_type === "Labour Type") {
            $per_day_amount = $canteen_amount;
            
            if ($employee->workingHoursIncludeLunch === null) {
                return count($work_days) * $per_day_amount * 2;
            }
                
            $threshold = $this->hh_dot_mm_to_minutes($employee->workingHoursIncludeLunch);
            
            $heavy_working_days = 0;
            foreach ($work_days as $date_val) {
                $worked_min = $daily_worked_minutes[$date_val] ?? 0;
                if ($worked_min > $threshold) {
                    $heavy_working_days += 1;
                }
            }
                     
            $light_days = count($work_days) - $heavy_working_days;
            return ($light_days * $per_day_amount * 2) + ($heavy_working_days * $per_day_amount);
        } else {
            return 0;
        }
    }

    public function hh_dot_mm_to_minutes($value) {
        if ($value === null) {
            return 0;
        }
        try {
            $val = (float)$value;
            $hours = (int)$val;
            $minutes = (int)round(($val - $hours) * 100);
            if ($minutes < 0 || $minutes > 59) {
                throw new Exception("Invalid minutes: " . $minutes);
            }
            return ($hours * 60) + $minutes;
        } catch (Exception $e) {
            return 0;
        }
    }

    public function calculate_late_entry_penalty(CompanyEmployee $employee, DateTime $time_in_date, DateTimeZone $tz) {
        $shift = $employee->companyShift ? DbHelper::findById(CompanyShift::class, $employee->companyShift) : null;
        if (!$shift || !$shift->startTime) {
            return 0;
        }
        
        $basic = $employee->basicSalary;
        if (!$basic || $basic <= 0) {
            return 0;
        }
        
        $day_salary = (int)floor($basic / 30);
        $total_hours = $shift->totalHours !== null ? (float)$shift->totalHours : 0.0;

        $actual_in = clone $time_in_date;
        $actual_in->setTimezone($tz);
        
        $raw_start_str = $shift->startTime;
        if ($raw_start_str) {
            try {
                $utc_tz = new \DateTimeZone("UTC");
                if (strlen(trim($raw_start_str)) > 8) {
                    $expected_start = new DateTime($raw_start_str, $utc_tz);
                } else {
                    $expected_start = new DateTime($actual_in->format("Y-m-d ") . trim($raw_start_str), $utc_tz);
                }
                $expected_start->setTimezone($tz);
                $expected_start->setDate(
                    (int)$actual_in->format("Y"),
                    (int)$actual_in->format("m"),
                    (int)$actual_in->format("d")
                );
            } catch (Exception $e) {
                $expected_start = new DateTime($actual_in->format("Y-m-d ") . "00:00:00", $tz);
            }
        } else {
            $expected_start = new DateTime($actual_in->format("Y-m-d ") . "00:00:00", $tz);
        }

        $late_minutes = (int)(($actual_in->getTimestamp() - $expected_start->getTimestamp()) / 60);
        
        if ($late_minutes <= 0) {
            return 0;
        }
            
        return $this->pick_and_apply_rule($employee, $day_salary, $total_hours, $late_minutes, false);
    }

    public function calculate_early_exit_penalty(CompanyEmployee $employee, DateTime $time_out_date, DateTimeZone $tz) {
        $shift = $employee->companyShift ? DbHelper::findById(CompanyShift::class, $employee->companyShift) : null;
        if (!$shift || !$shift->endTime) {
            return 0;
        }
            
        $basic = $employee->basicSalary;
        if (!$basic || $basic <= 0) {
            return 0;
        }
            
        $day_salary = (int)floor($basic / 30);
        $total_hours = $shift->totalHours !== null ? (float)$shift->totalHours : 0.0;

        $actual_out = clone $time_out_date;
        $actual_out->setTimezone($tz);
        
        $raw_end_str = $shift->endTime;
        if ($raw_end_str) {
            try {
                $utc_tz = new \DateTimeZone("UTC");
                if (strlen(trim($raw_end_str)) > 8) {
                    $expected_end = new DateTime($raw_end_str, $utc_tz);
                } else {
                    $expected_end = new DateTime($actual_out->format("Y-m-d ") . trim($raw_end_str), $utc_tz);
                }
                $expected_end->setTimezone($tz);
                $expected_end->setDate(
                    (int)$actual_out->format("Y"),
                    (int)$actual_out->format("m"),
                    (int)$actual_out->format("d")
                );
            } catch (Exception $e) {
                $expected_end = new DateTime($actual_out->format("Y-m-d ") . "00:00:00", $tz);
            }
        } else {
            $expected_end = new DateTime($actual_out->format("Y-m-d ") . "00:00:00", $tz);
        }

        $early_minutes = (int)(($expected_end->getTimestamp() - $actual_out->getTimestamp()) / 60);
        
        if ($early_minutes <= 0) {
            return 0;
        }
            
        return $this->pick_and_apply_rule($employee, $day_salary, $total_hours, $early_minutes, true);
    }

    public function pick_and_apply_rule(CompanyEmployee $employee, $day_salary, $total_hours, $diff_minutes, $type_val) {
        $rules = DbHelper::findAll(AttendancePenaltyRules::class, "company_id = :comp_id AND is_early_exit = :is_early", [
            "comp_id" => $employee->companyDetails,
            "is_early" => $type_val ? 1 : 0
        ]);
        if (empty($rules)) {
            return 0;
        }

        usort($rules, function($a, $b) {
            $a_min = $a->minutes !== null ? (int)$a->minutes : 0;
            $b_min = $b->minutes !== null ? (int)$b->minutes : 0;
            return $a_min <=> $b_min;
        });

        $chosen_rule = null;
        foreach ($rules as $r) {
            $rule_min = $r->minutes !== null ? (int)$r->minutes : 0;
            if ($diff_minutes >= $rule_min) {
                $chosen_rule = $r;
            } else if ($chosen_rule === null) {
                $chosen_rule = $r;
            }
        }

        if (!$chosen_rule) {
            return 0;
        }

        return $this->compute_penalty($chosen_rule, $day_salary, $total_hours, $diff_minutes);
    }

    public function compute_penalty(AttendancePenaltyRules $rule, $day_salary, $total_hours, $diff_minutes = 0) {
        if (!$total_hours || $total_hours <= 0) {
            $total_hours = 8.0;
        }

        $per_hour_salary = $day_salary / $total_hours;
        $per_hour_salary = round($per_hour_salary, 2, PHP_ROUND_HALF_UP);

        $per_minute_salary = $per_hour_salary / 60.0;
        $per_minute_salary = round($per_minute_salary, 2, PHP_ROUND_HALF_UP);

        $deduction_type = $rule->deductionType;
        if (!$deduction_type) {
            return 0;
        }

        $deduction_type = trim($deduction_type);

        $penalty_hours = $diff_minutes > 0 ? (int)ceil($diff_minutes / 60.0) : 1;

        if ($deduction_type === "Fixed Amount") {
            return $penalty_hours * ($rule->amount !== null ? (int)$rule->amount : 0);
        } else if ($deduction_type === "5 Min Salary" || $deduction_type === "15 Min Salary" || $deduction_type === "30 Min Salary" || $deduction_type === "1 Hour Salary") {
            return $penalty_hours * (int)round($per_hour_salary);
        } else if ($deduction_type === "Half Day Salary") {
            return (int)floor($day_salary / 2);
        } else if ($deduction_type === "1 Day Salary") {
            return (int)$day_salary;
        } else if ($deduction_type === "1.5 Day Salary") {
            return (int)round($day_salary * 1.5);
        } else if ($deduction_type === "2 Day Salary") {
            return (int)($day_salary * 2);
        } else if ($deduction_type === "2.5 Day Salary") {
            return (int)round($day_salary * 2.5);
        } else if ($deduction_type === "3 Day Salary") {
            return (int)($day_salary * 3);
        } else {
            return 0;
        }
    }

    public function calculate_total_allowance_and_deductions($user_id, $type_str) {
        $deductions_list = DbHelper::findAll(Deductions::class, "employee_id = :emp_id AND type = :type", [
            "emp_id" => $user_id,
            "type" => $type_str
        ]);
        $res = [];
        foreach ($deductions_list as $d) {
            $res[] = [
                "label" => $d->label,
                "amount" => $d->amount !== null ? (float)$d->amount : 0.0,
                "type" => $d->type
            ];
        }
        return $res;
    }
}
