<?php
namespace Common\Services;

use Common\Models\CompanyEmployeeRoles;
use Common\Models\CompanyDetails;
use Common\Models\CompanyFunctionality;
use Common\Models\CompanyModules;
use Common\Models\CompanyModuleActions;
use Common\Models\CompanyRoleModuleActions;
use Exception;
use PDO;

class CompanyEmployeeRoleService {
    
    public function get_all_roles_list() {
        try {
            $roles = DbHelper::findAll(CompanyEmployeeRoles::class, "1=1", [], "id ASC");
            $role_dto_list = [];
            foreach ($roles as $role) {
                $role_dto_list[] = [
                    "roleName" => $role->roleName,
                    "roleId" => $role->roleId
                ];
            }
            return $role_dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function roles_list($search_key, $page, $size) {
        try {
            $db = DbHelper::getDb();
            $where = "1=1";
            $params = [];
            
            if ($search_key) {
                $where = "role_name LIKE :search_key";
                $params['search_key'] = "%" . $search_key . "%";
            }

            // Count total records
            $countSql = "SELECT COUNT(*) FROM company_employee_roles WHERE " . $where;
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $total_records = (int)$countStmt->fetchColumn();

            $limit = ($size > 0) ? (int)$size : 10;
            $total_pages = (int)ceil($total_records / $limit);
            
            // Adjust offset
            $django_page_num = $page + 1; // 1-indexed for response math
            if ($django_page_num > $total_pages && $total_pages > 0) {
                $django_page_num = 1;
            }
            $offset = ($django_page_num - 1) * $limit;

            $roles = DbHelper::findAll(
                CompanyEmployeeRoles::class,
                $where,
                $params,
                "id ASC",
                "$offset, $limit"
            );

            $roles_dtos = [];
            foreach ($roles as $role) {
                $roles_dtos[] = [
                    "roleId" => $role->roleId,
                    "companyId" => $role->companyDetails,
                    "roleName" => $role->roleName,
                    "rolesActions" => $this->get_policy($role->roleId)
                ];
            }

            return [
                "getTotalPages" => $total_pages ?: 1,
                "getNumber" => $django_page_num - 1,
                "getSize" => $limit,
                "getTotalRecords" => $total_records,
                "rolesList" => $roles_dtos
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_roles() {
        try {
            // exclude roleName = "owner"
            $roles = DbHelper::findAll(CompanyEmployeeRoles::class, "LOWER(role_name) != LOWER(:owner)", ["owner" => "owner"], "id ASC");
            $roles_dtos = [];
            foreach ($roles as $role) {
                $roles_dtos[] = [
                    "roleId" => $role->roleId,
                    "companyId" => $role->companyDetails,
                    "roleName" => $role->roleName
                ];
            }
            return ["rolesList" => $roles_dtos];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_roles_by_company_id($company_id) {
        try {
            $roles = DbHelper::findAll(CompanyEmployeeRoles::class, "company_id = :company_id", ["company_id" => $company_id], "id ASC");
            $company_employee_roles_dto_list = [];
            foreach ($roles as $role) {
                $company_employee_roles_dto_list[] = $this->get_role($role->roleId);
            }
            return $company_employee_roles_dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_role($id) {
        try {
            $role = DbHelper::findById(CompanyEmployeeRoles::class, $id);
            if (!$role) {
                throw new Exception("Role not found");
            }
            return [
                "roleId" => $role->roleId,
                "companyId" => $role->companyDetails,
                "roleName" => $role->roleName,
                "rolesActions" => $this->get_policy($id)
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_role($company_employee_roles_dto) {
        try {
            $db = DbHelper::getDb();
            $db->beginTransaction();

            $company_id = $company_employee_roles_dto['companyId'] ?? null;
            $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company_details) {
                throw new Exception("Company not found");
            }

            $role = new CompanyEmployeeRoles();
            $role->companyDetails = $company_id;
            $role->roleName = $company_employee_roles_dto['roleName'] ?? null;
            $role = DbHelper::insert($role);

            $roles_actions = $company_employee_roles_dto['rolesActions'] ?? null;
            if ($roles_actions) {
                $this->save_policy_internal($role->roleId, $roles_actions);
            }

            $db->commit();

            return [
                "roleId" => $role->roleId,
                "companyId" => $company_details->id,
                "roleName" => $role->roleName,
                "rolesActions" => $roles_actions
            ];
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            throw new Exception($e->getMessage());
        }
    }

    public function update_role($id, $company_employee_roles_dto) {
        try {
            $db = DbHelper::getDb();
            $db->beginTransaction();

            $company_id = $company_employee_roles_dto['companyId'] ?? null;
            $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company_details) {
                throw new Exception("Company not found");
            }

            $role = DbHelper::findById(CompanyEmployeeRoles::class, $id);
            if (!$role) {
                throw new Exception("Role not found");
            }

            $role->companyDetails = $company_id;
            $role->roleName = $company_employee_roles_dto['roleName'] ?? null;
            DbHelper::update($role);

            $roles_actions = $company_employee_roles_dto['rolesActions'] ?? null;
            if ($roles_actions) {
                $this->save_policy_internal($id, $roles_actions);
            }

            $db->commit();

            return [
                "roleId" => $role->roleId,
                "companyId" => $company_details->id,
                "roleName" => $role->roleName,
                "rolesActions" => $roles_actions
            ];
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            throw new Exception($e->getMessage());
        }
    }

    public function delete_role($id) {
        try {
            $role = DbHelper::findById(CompanyEmployeeRoles::class, $id);
            if (!$role) {
                throw new Exception("Role not found");
            }
            DbHelper::delete(CompanyEmployeeRoles::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_policy($role_id) {
        try {
            $role_id = (int)$role_id;
            if ($role_id !== 0) {
                $role = DbHelper::findById(CompanyEmployeeRoles::class, $role_id);
                if (!$role) {
                    throw new Exception("No such Role Exist");
                }
            }

            $functionalities = [];
            $functionality_list = DbHelper::findAll(CompanyFunctionality::class, "1=1", [], "id ASC");

            foreach ($functionality_list as $functionality) {
                $modules = [];
                $module_list = DbHelper::findAll(CompanyModules::class, "functionality_id = :func_id", ["func_id" => $functionality->id], "id ASC");

                foreach ($module_list as $module) {
                    $module_policies = DbHelper::findAll(CompanyModuleActions::class, "module_id = :mod_id", ["mod_id" => $module->moduleId]);
                    $module_assigned_policy = [];
                    foreach ($module_policies as $mp) {
                        if ($mp->action !== null) {
                            $module_assigned_policy[] = (int)$mp->action;
                        }
                    }
                    sort($module_assigned_policy);

                    $role_assigned_policy = [];
                    if ($role_id !== 0) {
                        // find CompanyRoleModuleActions for role and this module
                        $db = DbHelper::getDb();
                        $rmaSql = "SELECT rma.* FROM company_role_module_actions rma 
                                   INNER JOIN company_module_actions ma ON rma.module_action_Id = ma.id 
                                   WHERE rma.role_id = :role_id AND ma.module_id = :module_id";
                        $stmt = $db->prepare($rmaSql);
                        $stmt->execute(["role_id" => $role_id, "module_id" => $module->moduleId]);
                        $rma_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $action_ids = [];
                        foreach ($rma_rows as $row) {
                            $ma = DbHelper::findById(CompanyModuleActions::class, $row['module_action_Id']);
                            if ($ma && $ma->action !== null) {
                                $action_ids[] = (int)$ma->action;
                            }
                        }
                        $role_assigned_policy = array_values(array_unique($action_ids));
                        sort($role_assigned_policy);
                    }

                    $modules[] = [
                        "moduleId" => $module->moduleId,
                        "moduleName" => $module->moduleName,
                        "moduleAssignedActions" => $module_assigned_policy,
                        "roleAssignedActions" => $role_assigned_policy
                    ];
                }

                $functionalities[] = [
                    "functionalityId" => $functionality->id,
                    "functionalityName" => $functionality->functionalityName,
                    "modules" => $modules
                ];
            }

            return [
                "functionalities" => $functionalities
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function save_policy($role_id, $role_action_dto) {
        $db = DbHelper::getDb();
        try {
            $db->beginTransaction();
            $this->save_policy_internal($role_id, $role_action_dto);
            $db->commit();
            return $role_action_dto;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw new Exception($e->getMessage());
        }
    }

    private function save_policy_internal($role_id, $role_action_dto) {
        $role = DbHelper::findById(CompanyEmployeeRoles::class, $role_id);
        if (!$role) {
            throw new Exception("Role not found");
        }

        // Delete existing CompanyRoleModuleActions for this role
        $db = DbHelper::getDb();
        $delStmt = $db->prepare("DELETE FROM company_role_module_actions WHERE role_id = :role_id");
        $delStmt->execute(["role_id" => $role_id]);

        $functionalities = $role_action_dto['functionalities'] ?? [];
        foreach ($functionalities as $func_data) {
            $modules = $func_data['modules'] ?? [];
            foreach ($modules as $mod_data) {
                $module_id = $mod_data['moduleId'] ?? null;
                $role_assigned_actions = $mod_data['roleAssignedActions'] ?? [];

                if (!empty($role_assigned_actions)) {
                    foreach ($role_assigned_actions as $action_id) {
                        $module_policy = DbHelper::findFirst(CompanyModuleActions::class, "module_id = :mod_id AND action_id = :act_id", [
                            "mod_id" => $module_id,
                            "act_id" => $action_id
                        ]);
                        if ($module_policy) {
                            $rma = new CompanyRoleModuleActions();
                            $rma->role = $role_id;
                            $rma->moduleActions = $module_policy->moduleActionId;
                            DbHelper::insert($rma);
                        }
                    }
                }
            }
        }
    }
}
