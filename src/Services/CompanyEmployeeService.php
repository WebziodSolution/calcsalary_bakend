<?php
namespace Common\Services;

use Common\Models\CompanyEmployee;
use Common\Models\CompanyDetails;
use Common\Models\CompanyEmployeeRoles;
use Common\Models\Department;
use Common\Models\EmployeeType;
use Common\Models\CompanyShift;
use Common\Models\UserInOut;
use Common\Models\WeeklyOff;
use Common\Models\HolidayTemplates;
use Common\Models\OvertimeRules;
use Common\Models\EmployeeBackAccountInfo;
use Common\Models\SalaryStatementHistory;
use Common\Exception\GlobalException;
use Exception;

class CompanyEmployeeService {
    private $common_service;
    private $company_employee_role_service;

    public function __construct() {
        $this->common_service = new CommonService();
        $this->company_employee_role_service = new CompanyEmployeeRoleService();
    }

    public function get_reports($company_id, $type_str, $month, $user_time_zone) {
        try {
            $year = (int)date("Y");
            $month_val = (int)$month + 1;

            $start_date = sprintf("%04d-%02d-01 00:00:00", $year, $month_val);
            $last_day = date("t", strtotime(sprintf("%04d-%02d-01", $year, $month_val)));
            $end_date = sprintf("%04d-%02d-%02d 23:59:59", $year, $month_val, $last_day);

            $user_in_outs = DbHelper::findAll(
                UserInOut::class,
                "company_id = :company_id AND created_on >= :start_date AND created_on <= :end_date AND is_salary_generate = 1",
                [
                    "company_id" => $company_id,
                    "start_date" => $start_date,
                    "end_date" => $end_date
                ]
            );

            $unique_keys = [];
            foreach ($user_in_outs as $u) {
                if ($u->user && $u->companyDetails) {
                    $unique_keys[] = $u->user . "|" . $u->companyDetails;
                }
            }
            $unique_keys = array_unique($unique_keys);

            $results = [];
            foreach ($unique_keys as $key) {
                $parts = explode("|", $key);
                $employee_id = (int)$parts[0];
                $comp_id = (int)$parts[1];

                $history_list = DbHelper::findAll(
                    SalaryStatementHistory::class,
                    "employee_id = :emp_id AND company_id = :comp_id",
                    [
                        "emp_id" => $employee_id,
                        "comp_id" => $comp_id
                    ]
                );

                foreach ($history_list as $history) {
                    $company_employee = DbHelper::findById(CompanyEmployee::class, $employee_id);
                    if (!$company_employee) {
                        continue;
                    }

                    $fullName = trim(($company_employee->firstName ?: "") . " " . ($company_employee->lastName ?: ""));
                    $userName = $fullName ?: $company_employee->userName;

                    $record = [
                        "userName" => $history->employeeName ?: $userName
                    ];

                    if ($company_employee->isPf && $type_str === "PF") {
                        $record["employee_pf_amount"] = $history->totalPfAmount;
                        $record["employer_pf_amount"] = $history->totalPfAmount;
                        $record["total_amount"] = ($history->totalPfAmount ?: 0) * 2;
                        $results[] = $record;
                    } else if ($company_employee->isPt && $type_str === "PT") {
                        $record["pt_amount"] = $company_employee->ptAmount;
                        $results[] = $record;
                    }
                }
            }

            return $results;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_employee_list_by_company_id($company_id) {
        try {
            $employees = DbHelper::findAll(CompanyEmployee::class, "company_id = :company_id", ["company_id" => $company_id], "id ASC");
            $response = [];
            foreach ($employees as $emp) {
                $fullName = trim(($emp->firstName ?: "") . " " . ($emp->lastName ?: ""));
                $response[] = [
                    "employeeId" => (int)$emp->employeeId,
                    "userName" => $fullName ?: $emp->userName
                ];
            }
            return $response;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_employee_by_company_id($company_id) {
        try {
            $employees = DbHelper::findAll(CompanyEmployee::class, "company_id = :company_id", ["company_id" => $company_id], "id ASC");
            $response = [];
            foreach ($employees as $emp) {
                $response[] = $this->get_employee($emp->employeeId);
            }
            return $response;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_employee($id) {
        try {
            $employee = DbHelper::findById(CompanyEmployee::class, $id);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $bank_info = DbHelper::findFirst(EmployeeBackAccountInfo::class, "employee_id = :emp_id", ["emp_id" => $id]);
            $bank_account_id = $bank_info ? $bank_info->id : null;

            $shift_dto = null;
            if ($employee->companyShift) {
                $shift = DbHelper::findById(CompanyShift::class, $employee->companyShift);
                if ($shift) {
                    $shift_dto = [
                        "id" => $shift->id,
                        "companyId" => $shift->companyDetails,
                        "shiftName" => $shift->shiftName,
                        "shiftType" => $shift->shiftType,
                        "startTime" => $shift->startTime,
                        "endTime" => $shift->endTime,
                        "hours" => $shift->hours,
                        "totalHours" => $shift->totalHours
                    ];
                }
            }

            $role_dto = null;
            if ($employee->roles) {
                $role_dto = $this->company_employee_role_service->get_role($employee->roles);
            }

            $dept = $employee->department ? DbHelper::findById(Department::class, $employee->department) : null;
            $empType = $employee->employeeType ? DbHelper::findById(EmployeeType::class, $employee->employeeType) : null;

            return [
                "employeeId" => (int)$employee->employeeId,
                "companyId" => (int)$employee->companyDetails,
                "roleId" => (int)$employee->roles,
                "userName" => $employee->userName,
                "firstName" => $employee->firstName,
                "lastName" => $employee->lastName,
                "email" => $employee->email,
                "password" => $employee->password,
                "phone" => $employee->phone,
                "emergencyPhone" => $employee->emergencyPhone,
                "altPhone" => $employee->altPhone,
                "profileImage" => $employee->profileImage,
                "gender" => $employee->gender,
                "dob" => $employee->dob instanceof \DateTimeInterface ? $employee->dob->format("Y-m-d") : ($employee->dob ? date("Y-m-d", strtotime($employee->dob)) : null),
                "zipCode" => $employee->zipCode,
                "city" => $employee->city,
                "state" => $employee->state,
                "country" => $employee->country,
                "hourlyRate" => $employee->hourlyRate,
                "address1" => $employee->address1,
                "address2" => $employee->address2,
                "roleName" => $role_dto ? $role_dto['roleName'] : null,
                "middleName" => $employee->middleName,
                "emergencyContact" => $employee->emergencyContact,
                "contactPhone" => $employee->contactPhone,
                "relationship" => $employee->relationship,
                "departmentId" => (int)$employee->department,
                "departmentName" => $dept ? $dept->departmentName : null,
                "employeeTypeId" => (int)$employee->employeeType,
                "employeeTypeName" => $empType ? $empType->name : null,
                "payPeriod" => (int)$employee->payPeriod,
                "hiredDate" => $employee->hiredDate instanceof \DateTimeInterface ? $employee->hiredDate->format("Y-m-d") : ($employee->hiredDate ? date("Y-m-d", strtotime($employee->hiredDate)) : null),
                "bankAccountId" => (int)$bank_account_id,
                "isActive" => (int)$employee->isActive,
                "shiftId" => (int)$employee->companyShift,
                "companyLocation" => $employee->companyLocation,
                "checkGeofence" => (int)$employee->checkGeofence,
                "embedding" => $employee->embedding,
                "bloodGroup" => $employee->bloodGroup,
                "aadharImage" => $employee->aadharImage,
                "isPf" => $employee->isPf,
                "pfType" => $employee->pfType,
                "pfPercentage" => (int)$employee->pfPercentage,
                "pfAmount" => (int)$employee->pfAmount,
                "isPt" => $employee->isPt,
                "ptAmount" => (int)$employee->ptAmount,
                "basicSalary" => (int)$employee->basicSalary,
                "grossSalary" => (int)$employee->grossSalary,
                "canteenType" => $employee->canteenType,
                "canteenAmount" => (int)$employee->canteenAmount,
                "otId" => (int)$employee->overtimeRules,
                "lunchBreak" => (int)$employee->lunchBreak,
                "workingHoursIncludeLunch" => (int)$employee->workingHoursIncludeLunch,
                "weeklyOffId" => (int)$employee->weeklyOff,
                "holidayTemplateId" => (int)$employee->holidayTemplates,
                "earlyExitPenaltyRule" => (int)$employee->earlyExitPenaltyRule,
                "lateEntryPenaltyRule" => (int)$employee->lateEntryPenaltyRule,
                "companyShiftDto" => $shift_dto,
                "companyEmployeeRolesDto" => $role_dto
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_employee($dto) {
        try {
            $company_id = $dto['companyId'] ?? null;
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            $role_id = $dto['roleId'] ?? null;
            $role = DbHelper::findById(CompanyEmployeeRoles::class, $role_id);
            if (!$role) {
                throw new Exception("Role not found");
            }

            $department_id = $dto['departmentId'] ?? null;
            $department = null;
            if ($department_id) {
                $department = DbHelper::findById(Department::class, $department_id);
                if (!$department) {
                    throw new Exception("Department not found");
                }
            }

            $employee_type_id = $dto['employeeTypeId'] ?? null;
            $employee_type = null;
            if ($employee_type_id) {
                $employee_type = DbHelper::findById(EmployeeType::class, $employee_type_id);
                if (!$employee_type) {
                    throw new Exception("Employee type not found");
                }
            }

            $shift_id = $dto['shiftId'] ?? null;
            $shift = null;
            if ($shift_id) {
                $shift = DbHelper::findById(CompanyShift::class, $shift_id);
                if (!$shift) {
                    throw new Exception("Shift not found");
                }
            }

            $is_exists = DbHelper::findFirst(CompanyEmployee::class, "company_id = :company_id AND user_name = :username", [
                "company_id" => $company_id,
                "username" => $dto['userName'] ?? ""
            ]);
            if ($is_exists) {
                throw new Exception("User name is already taken");
            }

            $employee = new CompanyEmployee();

            if (!empty($dto['hiredDate'])) {
                $employee->hiredDate = $this->common_service->convert_string_to_date($dto['hiredDate']);
            }
            if (!empty($dto['dob'])) {
                $employee->dob = $this->common_service->convert_string_to_date($dto['dob']);
            }

            $ot_id = $dto['otId'] ?? null;
            if ($ot_id) {
                $overtime_rules = DbHelper::findById(OvertimeRules::class, $ot_id);
                if (!$overtime_rules) {
                    throw new Exception("Overtime rule not found");
                }
                $employee->overtimeRules = $ot_id;
            }

            $weekly_off_id = $dto['weeklyOffId'] ?? null;
            if ($weekly_off_id) {
                $weekly_off = DbHelper::findById(WeeklyOff::class, $weekly_off_id);
                if (!$weekly_off) {
                    throw new Exception("Weekly off not found");
                }
                $employee->weeklyOff = $weekly_off_id;
            } else {
                $weekly_off = DbHelper::findFirst(WeeklyOff::class, "company_id = :company_id AND is_default = 1", ["company_id" => $company_id]);
                if ($weekly_off) {
                    $employee->weeklyOff = $weekly_off->id;
                }
            }

            $holiday_template_id = $dto['holidayTemplateId'] ?? null;
            if ($holiday_template_id) {
                $holiday_template = DbHelper::findById(HolidayTemplates::class, $holiday_template_id);
                if (!$holiday_template) {
                    throw new Exception("Holiday template not found");
                }
                $employee->holidayTemplates = $holiday_template_id;
            }

            $employee->companyDetails = $company_id;
            $employee->roles = $role_id;
            $employee->department = $department_id;
            $employee->employeeType = $employee_type_id;
            $employee->companyShift = $shift_id;

            $exclude_fields = [
                "employeeId", "companyId", "roleId", "departmentId",
                "employeeTypeId", "shiftId", "weeklyOffId", "holidayTemplateId", "otId",
                "dob", "hiredDate", "companyDetails", "roles", "department",
                "employeeType", "companyShift", "weeklyOff", "holidayTemplates", "overtimeRules"
            ];
            foreach ($dto as $field => $value) {
                if (!in_array($field, $exclude_fields) && property_exists($employee, $field)) {
                    $employee->$field = $value;
                }
            }

            $employee = DbHelper::insert($employee);
            return $this->get_employee($employee->employeeId);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_employee($id, $dto) {
        try {
            $employee = DbHelper::findById(CompanyEmployee::class, $id);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $company_id = $dto['companyId'] ?? null;
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            $role_id = $dto['roleId'] ?? null;
            $role = DbHelper::findById(CompanyEmployeeRoles::class, $role_id);
            if (!$role) {
                throw new Exception("Role not found");
            }

            $department_id = $dto['departmentId'] ?? null;
            $department = null;
            if ($department_id) {
                $department = DbHelper::findById(Department::class, $department_id);
                if (!$department) {
                    throw new Exception("Department not found");
                }
            }

            $employee_type_id = $dto['employeeTypeId'] ?? null;
            $employee_type = null;
            if ($employee_type_id) {
                $employee_type = DbHelper::findById(EmployeeType::class, $employee_type_id);
                if (!$employee_type) {
                    throw new Exception("Employee type not found");
                }
            }

            $shift_id = $dto['shiftId'] ?? null;
            $shift = null;
            if ($shift_id) {
                $shift = DbHelper::findById(CompanyShift::class, $shift_id);
                if (!$shift) {
                    throw new Exception("Shift not found");
                }
            }

            $is_exists = DbHelper::findFirst(CompanyEmployee::class, "company_id = :company_id AND user_name = :username", [
                "company_id" => $company_id,
                "username" => $dto['userName']                  
            ]);
            if ($is_exists && $is_exists->employeeId != $id) {
                throw new Exception("User name is already taken");
            }

            if (!empty($dto['hiredDate'])) {
                $employee->hiredDate = $this->common_service->convert_string_to_date($dto['hiredDate']);
            } else {
                $employee->hiredDate = null;
            }

            if (!empty($dto['dob'])) {
                $employee->dob = $this->common_service->convert_string_to_date($dto['dob']);
            } else {
                $employee->dob = null;
            }

            $ot_id = $dto['otId'] ?? null;
            if ($ot_id) {
                $overtime_rules = DbHelper::findById(OvertimeRules::class, $ot_id);
                if (!$overtime_rules) {
                    throw new Exception("Overtime rule not found");
                }
                $employee->overtimeRules = $ot_id;
            } else {
                $employee->overtimeRules = null;
            }

            $weekly_off_id = $dto['weeklyOffId'] ?? null;
            if ($weekly_off_id) {
                $weekly_off = DbHelper::findById(WeeklyOff::class, $weekly_off_id);
                if (!$weekly_off) {
                    throw new Exception("Weekly off not found");
                }
                $employee->weeklyOff = $weekly_off_id;
            } else {
                $weekly_off = DbHelper::findFirst(WeeklyOff::class, "company_id = :company_id AND is_default = 1", ["company_id" => $company_id]);
                if ($weekly_off) {
                    $employee->weeklyOff = $weekly_off->id;
                }
            }

            $holiday_template_id = $dto['holidayTemplateId'] ?? null;
            if ($holiday_template_id) {
                $holiday_template = DbHelper::findById(HolidayTemplates::class, $holiday_template_id);
                if (!$holiday_template) {
                    throw new Exception("Holiday template not found");
                }
                $employee->holidayTemplates = $holiday_template_id;
            } else {
                $employee->holidayTemplates = null;
            }

            $employee->companyDetails = $company_id;
            $employee->roles = $role_id;
            $employee->department = $department_id;
            $employee->employeeType = $employee_type_id;
            $employee->companyShift = $shift_id;

            $exclude_fields = [
                "employeeId", "companyId", "roleId", "departmentId",
                "employeeTypeId", "shiftId", "weeklyOffId", "holidayTemplateId", "otId",
                "dob", "hiredDate", "companyDetails", "roles", "department",
                "employeeType", "companyShift", "weeklyOff", "holidayTemplates", "overtimeRules","profileImage"
            ];
            foreach ($dto as $field => $value) {
                if (!in_array($field, $exclude_fields) && property_exists($employee, $field)) {
                    $employee->$field = $value;
                }
            }

            DbHelper::update($employee);
            return $this->get_employee($employee->employeeId);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_employee($id) {
        try {
            $employee = DbHelper::findById(CompanyEmployee::class, $id);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $company_id = $employee->companyDetails;
            if ($company_id) {
                $this->delete_employee_profile($company_id, $id);
            }

            DbHelper::delete(CompanyEmployee::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function upload_employee_profile($company_id, $employee_id, $image_path) {
        try {
            $this->delete_employee_profile($company_id, $employee_id);
            $employee = DbHelper::findById(CompanyEmployee::class, $employee_id);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $updated_path = $this->common_service->update_file_location_for_profile(
                $image_path,
                $company_id,
                "employeeProfile/" . $employee_id
            );

            if ($updated_path === "Error") {
                return "Error";
            } else {
                $employee->profileImage = $updated_path;
                DbHelper::update($employee);
                return $updated_path;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_employee_profile($company_id, $employee_id) {
        try {
            $employee = DbHelper::findById(CompanyEmployee::class, $employee_id);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $config = require __DIR__ . '/../../config/settings.php';
            $file_dir = $config['timesheetpro_drive'] ?? '';

            $existing_image_path = $file_dir . DIRECTORY_SEPARATOR . $company_id . DIRECTORY_SEPARATOR . "employeeProfile" . DIRECTORY_SEPARATOR . $employee_id;
            if (file_exists($existing_image_path)) {
                $this->common_service->delete_directory_recursively($existing_image_path);
                $employee->profileImage = "";
                DbHelper::update($employee);
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function upload_employee_aadhar_image($company_id, $employee_id, $image_path) {
        try {
            $this->delete_employee_aadhar_image($company_id, $employee_id);
            $employee = DbHelper::findById(CompanyEmployee::class, $employee_id);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $updated_path = $this->common_service->update_file_location_for_profile(
                $image_path,
                $company_id,
                "employeeProfile/aadharImage/" . $employee_id
            );

            if ($updated_path === "Error") {
                return "Error";
            } else {
                $employee->aadharImage = $updated_path;
                DbHelper::update($employee);
                return $updated_path;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_employee_aadhar_image($company_id, $employee_id) {
        try {
            $employee = DbHelper::findById(CompanyEmployee::class, $employee_id);
            if (!$employee) {
                throw new Exception("Employee not found");
            }

            $config = require __DIR__ . '/../../config/settings.php';
            $file_dir = $config['timesheetpro_drive'] ?? '';

            $existing_image_path = $file_dir . DIRECTORY_SEPARATOR . $company_id . DIRECTORY_SEPARATOR . "employeeProfile" . DIRECTORY_SEPARATOR . "aadharImage" . DIRECTORY_SEPARATOR . $employee_id;
            if (file_exists($existing_image_path)) {
                $this->common_service->delete_directory_recursively($existing_image_path);
                $employee->aadharImage = "";
                DbHelper::update($employee);
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_last_user_id() {
        try {
            $last_emp = DbHelper::findFirst(CompanyEmployee::class, "1=1", [], "id DESC");
            if (!$last_emp) {
                return 0;
            }
            return (int)$last_emp->employeeId;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_employee_from_tsp($employee_dto) {
        try {
            $role_id = $employee_dto['roleId'] ?? null;
            $company_id = $employee_dto['companyId'] ?? null;

            $roles_exist = DbHelper::findFirst(CompanyEmployeeRoles::class, "company_id = :company_id", ["company_id" => $company_id]);
            if (!$roles_exist) {
                $roles_list = $employee_dto['roles'] ?? [];
                foreach ($roles_list as $role_data) {
                    if (($role_data['roleName'] ?? '') !== ($employee_dto['roleName'] ?? '')) {
                        $roles_dto = [
                            "companyId" => $company_id,
                            "roleName" => $role_data['roleName'] ?? '',
                            "rolesActions" => $role_data['rolesActions'] ?? []
                        ];
                        $this->company_employee_role_service->create_role($roles_dto);
                    }
                }

                $roles_actions = (!empty($roles_list)) ? end($roles_list)['rolesActions'] ?? null : null;
                $roles_dto = [
                    "companyId" => $company_id,
                    "roleName" => $employee_dto['roleName'] ?? '',
                    "rolesActions" => $roles_actions
                ];
                $created_role = $this->company_employee_role_service->create_role($roles_dto);
                $role_id = $created_role['roleId'];
            }

            $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company_details) {
                throw new Exception("Company not found");
            }

            $company_employee_roles = DbHelper::findById(CompanyEmployeeRoles::class, $role_id);
            if (!$company_employee_roles) {
                throw new Exception("Role not found");
            }

            $is_exists = DbHelper::findFirst(CompanyEmployee::class, "company_id = :company_id AND user_name = :username", [
                "company_id" => $company_id,
                "username" => $employee_dto['userName'] ?? ""
            ]);
            if ($is_exists) {
                throw new Exception("User name is already taken");
            }

            $employee = new CompanyEmployee();
            $employee->companyDetails = $company_id;
            $employee->roles = $role_id;
            $employee->lateEntryPenaltyRule = 1;
            $employee->earlyExitPenaltyRule = 1;

            $exclude_fields = [
                "employeeId", "companyId", "roleId", "departmentId",
                "employeeTypeId", "shiftId", "weeklyOffId", "holidayTemplateId", "otId",
                "dob", "hiredDate", "companyDetails", "roles", "department",
                "employeeType", "companyShift", "weeklyOff", "holidayTemplates", "overtimeRules"
            ];
            foreach ($employee_dto as $field => $value) {
                if (!in_array($field, $exclude_fields) && property_exists($employee, $field)) {
                    $employee->$field = $value;
                }
            }

            $employee = DbHelper::insert($employee);
            $employee_dto["employeeId"] = $employee->employeeId;
            return $employee_dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_employee_from_tsp($id, $employee_dto) {
        try {
            $employee = DbHelper::findById(CompanyEmployee::class, $id);
            if (!$employee) {
                throw new Exception("CompanyEmployee not found");
            }

            $company_id = $employee_dto['companyId'] ?? null;
            $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company_details) {
                throw new Exception("Company not found");
            }

            $role_id = $employee_dto['roleId'] ?? null;
            $company_employee_roles = DbHelper::findById(CompanyEmployeeRoles::class, $role_id);
            if (!$company_employee_roles) {
                throw new Exception("Role not found");
            }

            $is_exists = DbHelper::findFirst(CompanyEmployee::class, "company_id = :company_id AND user_name = :username AND id != :emp_id", [
                "company_id" => $company_id,
                "username" => $employee_dto['userName'] ?? "",
                "emp_id" => $id
            ]);
            if ($is_exists) {
                throw new Exception("User name is already taken");
            }

            $employee->companyDetails = $company_id;
            $employee->roles = $role_id;
            $employee->lateEntryPenaltyRule = 1;
            $employee->earlyExitPenaltyRule = 1;

            $exclude_fields = [
                "employeeId", "companyId", "roleId", "departmentId",
                "employeeTypeId", "shiftId", "weeklyOffId", "holidayTemplateId", "otId",
                "dob", "hiredDate", "companyDetails", "roles", "department",
                "employeeType", "companyShift", "weeklyOff", "holidayTemplates", "overtimeRules"
            ];
            foreach ($employee_dto as $field => $value) {
                if (!in_array($field, $exclude_fields) && property_exists($employee, $field)) {
                    $employee->$field = $value;
                }
            }

            DbHelper::update($employee);
            $employee_dto["employeeId"] = $employee->employeeId;
            return $employee_dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
