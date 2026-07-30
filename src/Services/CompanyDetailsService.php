<?php
namespace Common\Services;

use Common\Models\CompanyDetails;
use Common\Models\Locations;
use Common\Models\CompanyTheme;
use Common\Models\CompanyEmployee;
use Common\Models\CompanyEmployeeRoles;
use Common\Models\CompanyModuleActions;
use Common\Models\CompanyRoleModuleActions;
use Common\Specifications\CompanySpecification;
use Common\Exception\GlobalException;
use Exception;

class CompanyDetailsService {
    private $common_service;
    private $company_employee_role_service;

    public function __construct() {
        $this->common_service = new CommonService();
        $this->company_employee_role_service = new CompanyEmployeeRoleService();
    }

    public function search_companies($name, $active) {
        try {
            $where = [];
            $params = [];

            if ($name) {
                $spec = CompanySpecification::search_by_name($name);
                $where[] = $spec['sql'];
                $params = array_merge($params, $spec['params']);
            }

            if ($active === 0 || $active === 1) {
                $spec = CompanySpecification::is_active($active === 1);
                $where[] = $spec['sql'];
                $params = array_merge($params, $spec['params']);
            }

            $whereSql = implode(" AND ", $where);
            $companies = DbHelper::findAll(CompanyDetails::class, $whereSql, $params);

            $simplified_list = [];
            foreach ($companies as $company) {
                $simplified_list[] = [
                    "id" => $company->id,
                    "companyName" => $company->companyName,
                    "companyLogo" => $company->companyLogo ?: ""
                ];
            }
            return $simplified_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_company_details($active) {
        try {
            if ($active === 2) {
                $companies = DbHelper::findAll(CompanyDetails::class);
            } else {
                $companies = DbHelper::findAll(CompanyDetails::class, "is_active = :is_active", ["is_active" => $active]);
            }

            $company_details_list = [];
            foreach ($companies as $company) {
                $company_details_list[] = [
                    "id" => $company->id,
                    "companyName" => $company->companyName,
                    "companyLogo" => $company->companyLogo ?: ""
                ];
            }
            return $company_details_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_company_details($company_id) {
        try {
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company details not found");
            }

            $locations = DbHelper::findAll(Locations::class, "company_id = :company_id", ["company_id" => $company_id]);
            $locations_dto_list = [];
            foreach ($locations as $loc) {
                $locations_dto_list[] = [
                    "companyId" => $loc->companyDetails,
                    "id" => $loc->id,
                    "city" => $loc->city ?: "",
                    "state" => $loc->state ?: "",
                    "country" => $loc->country ?: "",
                    "address1" => $loc->address1 ?: "",
                    "address2" => $loc->address2 ?: "",
                    "employeeCount" => $loc->employeeCount ?: 0,
                    "locationName" => $loc->locationName ?: "",
                    "externalId" => $loc->externalId ?: "",
                    "zipCode" => $loc->zipCode ?: "",
                    "geofenceId" => $loc->geofenceId ?: "",
                    "isActive" => $loc->isActive ?: 0
                ];
            }

            $register_date_str = $this->common_service->convert_date_to_string($company->registerDate);

            return [
                "id" => $company->id,
                "companyNo" => $company->companyNo ?: "",
                "companyName" => $company->companyName ?: "",
                "ein" => $company->ein ?: "",
                "organizationType" => $company->organizationType ?: "",
                "dba" => $company->dba ?: "",
                "email" => $company->email ?: "",
                "industryName" => $company->industryName ?: "",
                "phone" => $company->phone ?: "",
                "websiteUrl" => $company->websiteUrl ?: "",
                "registerDate" => $register_date_str ?: "",
                "companyLogo" => $company->companyLogo ?: "",
                "locations" => $locations_dto_list,
                "autoTimeInAfterHours" => $company->autoTimeInAfterHours ?: ""
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_company_details($company_dto, $step) {
        try {
            if ($step !== "1") {
                throw new GlobalException("Server Error");
            }

            $company_name = $company_dto['companyName'] ?? null;
            $ein = $company_dto['ein'] ?? null;

            $is_exists = DbHelper::findFirst(CompanyDetails::class, "company_name = :company_name", ["company_name" => $company_name]);
            if (!$is_exists && $ein) {
                $is_exists = DbHelper::findFirst(CompanyDetails::class, "EIN = :ein", ["ein" => $ein]);
            }
            if ($is_exists) {
                throw new GlobalException("{$company_name} is already registered");
            }

            if ($ein) {
                $is_ein_exists = DbHelper::findFirst(CompanyDetails::class, "EIN = :ein", ["ein" => $ein]);
                if ($is_ein_exists) {
                    throw new GlobalException("GST number {$ein} is already registered");
                }
            }

            $company = new CompanyDetails();
            $company->companyNo = $company_dto['companyNo'] ?? "";
            $company->companyName = $company_name ?: "";
            $company->dba = $company_dto['dba'] ?? "";
            $company->companyLogo = $company_dto['companyLogo'] ?? "";
            $company->email = $company_dto['email'] ?? "";
            $company->phone = $company_dto['phone'] ?? "";
            $company->industryName = $company_dto['industryName'] ?? "";
            $company->websiteUrl = $company_dto['websiteUrl'] ?? "";
            $company->isActive = 1;
            $company->registerDate = date("Y-m-d H:i:s");
            $company->ein = $ein ?: "";
            $company->organizationType = $company_dto['organizationType'] ?? "";
            $company->autoTimeInAfterHours = "20:00";

            $company = DbHelper::insert($company);

            $theme = new CompanyTheme();
            $theme->companyDetails = $company->id;
            $theme->primaryColor = "#666cff";
            $theme->textColor = "#262b43";
            $theme->sideNavigationBgColor = "#ffffff";
            $theme->headerBgColor = "#ffffff";
            $theme->contentBgColor = "#F5F5F7";
            $theme->iconColor = "#262b43";

            DbHelper::insert($theme);

            return $this->get_company_details($company->id);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function create_company_employee_role($role_dto) {
        try {
            $company_id = $role_dto['companyId'] ?? null;
            $role_name = $role_dto['roleName'] ?? null;

            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            $role = DbHelper::findFirst(CompanyEmployeeRoles::class, "company_id = :company_id AND role_name = :role_name", [
                "company_id" => $company_id,
                "role_name" => $role_name
            ]);

            if (!$role) {
                $role = new CompanyEmployeeRoles();
                $role->companyDetails = $company_id;
                $role->roleName = $role_name;
                $role = DbHelper::insert($role);
            }

            $roles_actions_data = $role_dto['rolesActions'] ?? [];
            if ($roles_actions_data && is_array($roles_actions_data)) {
                $functionalities = $roles_actions_data['functionalities'] ?? [];
                foreach ($functionalities as $func) {
                    $modules = $func['modules'] ?? [];
                    foreach ($modules as $mod) {
                        $module_id = $mod['moduleId'] ?? null;
                        $role_assigned_actions = $mod['roleAssignedActions'] ?? [];

                        foreach ($role_assigned_actions as $act) {
                            $action_id = null;
                            if (is_array($act)) {
                                $action_id = $act['actionId'] ?? ($act['id'] ?? null);
                            } else if (is_numeric($act) || is_string($act)) {
                                $action_id = (int)$act;
                            }

                            if ($action_id !== null) {
                                $module_action = DbHelper::findFirst(CompanyModuleActions::class, "module_id = :module_id AND action_id = :action_id", [
                                    "module_id" => $module_id,
                                    "action_id" => $action_id
                                ]);
                                if ($module_action) {
                                    $existing = DbHelper::findFirst(CompanyRoleModuleActions::class, "role_id = :role_id AND module_action_Id = :ma_id", [
                                        "role_id" => $role->roleId,
                                        "ma_id" => $module_action->moduleActionId
                                    ]);
                                    if (!$existing) {
                                        $rma = new CompanyRoleModuleActions();
                                        $rma->role = $role->roleId;
                                        $rma->moduleActions = $module_action->moduleActionId;
                                        DbHelper::insert($rma);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return [
                "roleId" => $role->roleId,
                "companyId" => $company->id,
                "roleName" => $role->roleName
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_employee_from_tsp($emp_dto) {
        try {
            $company_id = $emp_dto['companyId'] ?? null;
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            $role_id = $emp_dto['roleId'] ?? null;
            $role = DbHelper::findById(CompanyEmployeeRoles::class, $role_id);

            $username = $emp_dto['userName'] ?? null;
            $exists = DbHelper::findFirst(CompanyEmployee::class, "company_id = :company_id AND user_name = :username", [
                "company_id" => $company_id,
                "username" => $username
            ]);
            if ($exists) {
                throw new GlobalException("Username is already taken.");
            }

            $employee = new CompanyEmployee();
            $employee->companyDetails = $company_id;
            $employee->roles = $role_id;
            $employee->userName = $username;
            $employee->firstName = $emp_dto['firstName'] ?? null;
            $employee->lastName = $emp_dto['lastName'] ?? null;
            $employee->email = $emp_dto['email'] ?? null;
            $employee->password = $emp_dto['password'] ?? null;
            $employee->phone = $emp_dto['phone'] ?? null;
            $employee->address1 = $emp_dto['address1'] ?? null;
            $employee->city = $emp_dto['city'] ?? null;
            $employee->state = $emp_dto['state'] ?? null;
            $employee->country = $emp_dto['country'] ?? null;
            $employee->isActive = 1;
            $employee->checkGeofence = 0;

            $employee = DbHelper::insert($employee);
            return ["employeeId" => $employee->employeeId];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function update_company_details($company_id, $company_dto, $step) {
        try {
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company details not found");
            }

            if ($step === "1") {
                $company_name = $company_dto['companyName'] ?? null;
                $ein = $company_dto['ein'] ?? null;

                $is_exists = DbHelper::findFirst(CompanyDetails::class, "company_name = :company_name AND id != :company_id", [
                    "company_name" => $company_name,
                    "company_id" => $company_id
                ]);
                if (!$is_exists && $ein) {
                    $is_exists = DbHelper::findFirst(CompanyDetails::class, "EIN = :ein AND id != :company_id", [
                        "ein" => $ein,
                        "company_id" => $company_id
                    ]);
                }
                if ($is_exists) {
                    throw new GlobalException("{$company_name} is already registered");
                }

                if ($ein) {
                    $is_ein_exists = DbHelper::findFirst(CompanyDetails::class, "EIN = :ein AND id != :company_id", [
                        "ein" => $ein,
                        "company_id" => $company_id
                    ]);
                    if ($is_ein_exists) {
                        throw new GlobalException("GST number {$ein} is already registered");
                    }
                }

                $company->companyName = $company_name ?: "";
                $company->companyNo = $company_dto['companyNo'] ?? "";
                $company->dba = $company_dto['dba'] ?? "";
                $company->email = $company_dto['email'] ?? "";
                $company->phone = $company_dto['phone'] ?? "";
                $company->industryName = $company_dto['industryName'] ?? "";
                $company->websiteUrl = $company_dto['websiteUrl'] ?? "";
                $company->ein = $ein ?: "";
                $company->organizationType = $company_dto['organizationType'] ?? "";
                $company->isActive = 1;
                DbHelper::update($company);

                return $this->get_company_details($company->id);

            } else if ($step === "3") {
                $deleted_ids = $company_dto['deletedEmployeeId'] ?? [];
                if ($deleted_ids) {
                    foreach ($deleted_ids as $emp_id) {
                        DbHelper::delete(CompanyEmployee::class, $emp_id);
                    }
                }

                $employees = $company_dto['employees'] ?? [];
                if ($employees) {
                    $roles_list = DbHelper::findAll(CompanyEmployeeRoles::class, "company_id = :company_id", ["company_id" => $company_id]);

                    if (empty($roles_list)) {
                        $roles_dto_list = $company_dto['roles'] ?? [];
                        foreach ($employees as $emp_data) {
                            foreach ($roles_dto_list as $role_data) {
                                if (($role_data['roleName'] ?? '') !== ($emp_data['roleName'] ?? '')) {
                                    $this->create_company_employee_role([
                                        "companyId" => $company->id,
                                        "roleName" => $role_data['roleName'] ?? '',
                                        "rolesActions" => $role_data['rolesActions'] ?? []
                                    ]);
                                }
                            }
                            $role_payload = [
                                "companyId" => $company->id,
                                "roleName" => $emp_data['roleName'] ?? ''
                            ];
                            $matching_role = null;
                            foreach ($roles_dto_list as $r) {
                                if (($r['roleName'] ?? '') === ($emp_data['roleName'] ?? '')) {
                                    $matching_role = $r;
                                    break;
                                }
                            }
                            if (!$matching_role && !empty($roles_dto_list)) {
                                $matching_role = end($roles_dto_list);
                            }
                            if ($matching_role) {
                                $role_payload["rolesActions"] = $matching_role['rolesActions'] ?? [];
                            }

                            $created_role_info = $this->create_company_employee_role($role_payload);

                            $this->create_employee_from_tsp([
                                "companyId" => $company->id,
                                "roleId" => $created_role_info["roleId"],
                                "firstName" => $emp_data['firstName'] ?? null,
                                "lastName" => $emp_data['lastName'] ?? null,
                                "email" => $emp_data['email'] ?? null,
                                "phone" => $emp_data['phone'] ?? null,
                                "address1" => $emp_data['address1'] ?? null,
                                "city" => $emp_data['city'] ?? null,
                                "state" => $emp_data['state'] ?? null,
                                "country" => $emp_data['country'] ?? null,
                                "userName" => $emp_data['userName'] ?? null,
                                "password" => $emp_data['password'] ?? null
                            ]);
                        }
                    } else {
                        foreach ($employees as $emp_data) {
                            $role_name = $emp_data['roleName'] ?? '';
                            $role = DbHelper::findFirst(CompanyEmployeeRoles::class, "company_id = :company_id AND role_name = :role_name", [
                                "company_id" => $company_id,
                                "role_name" => $role_name
                            ]);
                            if (!$role) {
                                $role_dto_list = $company_dto['roles'] ?? [];
                                $matching_role = null;
                                foreach ($role_dto_list as $r) {
                                    if (($r['roleName'] ?? '') === $role_name) {
                                        $matching_role = $r;
                                        break;
                                    }
                                }
                                $role_payload = ["companyId" => $company->id, "roleName" => $role_name];
                                if ($matching_role) {
                                    $role_payload["rolesActions"] = $matching_role['rolesActions'] ?? [];
                                }
                                $created_role_info = $this->create_company_employee_role($role_payload);
                                $role_id = $created_role_info["roleId"];
                            } else {
                                $role_id = $role->roleId;
                            }

                            $this->create_employee_from_tsp([
                                "companyId" => $company->id,
                                "roleId" => $role_id,
                                "firstName" => $emp_data['firstName'] ?? null,
                                "lastName" => $emp_data['lastName'] ?? null,
                                "email" => $emp_data['email'] ?? null,
                                "phone" => $emp_data['phone'] ?? null,
                                "address1" => $emp_data['address1'] ?? null,
                                "city" => $emp_data['city'] ?? null,
                                "state" => $emp_data['state'] ?? null,
                                "country" => $emp_data['country'] ?? null,
                                "userName" => $emp_data['userName'] ?? null,
                                "password" => $emp_data['password'] ?? null
                            ]);
                        }
                    }
                }
                return $this->get_company_details($company->id);
            }
            return null;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function delete_company_details($company_id) {
        try {
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company details not found");
            }
            DbHelper::delete(CompanyDetails::class, $company_id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function upload_company_logo($company_id, $image_path) {
        try {
            $this->delete_company_logo($company_id);
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            $updated_path = $this->common_service->update_file_location_for_profile($image_path, $company_id, "companyLogo");
            if ($updated_path === "Error") {
                return "Error";
            } else {
                $company->companyLogo = $updated_path;
                DbHelper::update($company);
                return $updated_path;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_company_logo($company_id) {
        try {
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }

            $config = require __DIR__ . '/../../config/settings.php';
            $file_dir = $config['timesheetpro_drive'] ?? '';

            $existing_image_path = $file_dir . DIRECTORY_SEPARATOR . $company_id . DIRECTORY_SEPARATOR . "companyLogo";
            if (file_exists($existing_image_path)) {
                $this->common_service->delete_directory_recursively($existing_image_path);
                $company->companyLogo = "";
                DbHelper::update($company);
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_last_company() {
        try {
            $last_company = DbHelper::findFirst(CompanyDetails::class, "1=1", [], "id DESC");
            if ($last_company) {
                return $last_company->companyNo;
            }
            return null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_auto_time_in_after_hours($company_id, $data) {
        try {
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }
            $company->autoTimeInAfterHours = $data;
            DbHelper::update($company);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_auto_time_in_after_hours($company_id) {
        try {
            $company = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company) {
                throw new Exception("Company not found");
            }
            return $company->autoTimeInAfterHours;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
