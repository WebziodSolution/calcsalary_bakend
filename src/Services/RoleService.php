<?php
namespace Common\Services;

use Common\Models\Roles;
use Common\Models\Functionality;
use Common\Models\Module;
use Common\Models\ModuleActions;
use Common\Models\RoleModuleActions;
use Exception;

class RoleService {

    public function getAllRolesList() {
        try {
            $roles = DbHelper::findAll(Roles::class, "1=1", [], "role_Id ASC");
            $dtos = [];
            foreach ($roles as $r) {
                $dtos[] = [
                    "roleId" => $r->roleId,
                    "roleName" => $r->roleName
                ];
            }
            return $dtos;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function createRole($dto) {
        try {
            $role = new Roles();
            $role->roleName = $dto['roleName'] ?? null;
            $role = DbHelper::insert($role);

            $roles_actions = $dto['rolesActions'] ?? [];
            $this->savePolicy($role->roleId, $roles_actions);

            return [
                "roleId" => $role->roleId,
                "roleName" => $role->roleName,
                "rolesActions" => $roles_actions
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function rolesList($search_key, $page, $size) {
        try {
            $db = DbHelper::getDb();
            $limit = $size > 0 ? (int)$size : 10;
            $offset = $page * $limit;

            $params = [];
            if (!empty($search_key)) {
                $where = "`role_name` LIKE :search";
                $params["search"] = "%" . $search_key . "%";
            } else {
                $where = "1=1";
            }

            $count_sql = "SELECT COUNT(*) FROM `roles` WHERE $where";
            $count_stmt = $db->prepare($count_sql);
            $count_stmt->execute($params);
            $total_records = (int)$count_stmt->fetchColumn();

            $total_pages = (int)ceil($total_records / $limit);

            $sql = "SELECT * FROM `roles` WHERE $where ORDER BY `role_Id` ASC LIMIT $limit OFFSET $offset";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $dtos = [];
            foreach ($rows as $row) {
                $role = new Roles();
                foreach (Roles::$fieldsMap as $prop => $col) {
                    if (isset($row[$col])) {
                        $role->$prop = $row[$col];
                    }
                }

                $dtos[] = [
                    "roleId" => $role->roleId,
                    "roleName" => $role->roleName,
                    "rolesActions" => $this->getPolicy($role->roleId)
                ];
            }

            return [
                "getTotalPages" => $total_pages,
                "getNumber" => $page,
                "getSize" => $limit,
                "getTotalRecords" => $total_records,
                "rolesList" => $dtos
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getAllRoles() {
        try {
            $db = DbHelper::getDb();
            $stmt = $db->prepare("SELECT * FROM `roles` WHERE LOWER(`role_name`) != 'owner' ORDER BY `role_Id` ASC");
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $dtos = [];
            foreach ($rows as $row) {
                $role = new Roles();
                foreach (Roles::$fieldsMap as $prop => $col) {
                    if (isset($row[$col])) {
                        $role->$prop = $row[$col];
                    }
                }
                $dtos[] = [
                    "roleId" => $role->roleId,
                    "roleName" => $role->roleName
                ];
            }
            return ["rolesList" => $dtos];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getRoleById($role_id) {
        try {
            $role = DbHelper::findById(Roles::class, $role_id);
            if (!$role) {
                throw new Exception("Role not found");
            }
            return [
                "roleId" => $role->roleId,
                "roleName" => $role->roleName,
                "rolesActions" => $this->getPolicy($role->roleId)
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateById($role_id, $dto) {
        try {
            $role = DbHelper::findById(Roles::class, $role_id);
            if (!$role) {
                throw new Exception("Such Role Doesn't Exist");
            }

            $role->roleName = $dto['roleName'] ?? null;
            DbHelper::update($role);

            $roles_actions = $dto['rolesActions'] ?? [];
            $this->savePolicy($role->roleId, $roles_actions);

            return [
                "roleId" => $role->roleId,
                "roleName" => $role->roleName,
                "rolesActions" => $roles_actions
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function deleteRoleById($role_id) {
        try {
            $role = DbHelper::findById(Roles::class, $role_id);
            if (!$role) {
                throw new Exception("Role not found");
            }
            DbHelper::delete(Roles::class, $role_id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getPolicy($role_id) {
        try {
            $db = DbHelper::getDb();
            $func_list = DbHelper::findAll(Functionality::class, "1=1", [], "id ASC");
            
            $functionalities = [];
            foreach ($func_list as $func) {
                $modules = [];
                $module_list = DbHelper::findAll(Module::class, "functionality_id = :func_id", ["func_id" => $func->id], "module_Id ASC");
                
                foreach ($module_list as $module) {
                    $sql_ma = "SELECT action_id FROM `module_actions` WHERE module_id = :module_id ORDER BY action_id ASC";
                    $stmt_ma = $db->prepare($sql_ma);
                    $stmt_ma->execute(["module_id" => $module->moduleId]);
                    $module_assigned_policy = array_map('intval', $stmt_ma->fetchAll(\PDO::FETCH_COLUMN));

                    $role_assigned_policy = [];
                    if ($role_id !== 0) {
                        $sql = "SELECT ma.action_id 
                                FROM `role_module_actions` rma
                                JOIN `module_actions` ma ON rma.module_action_Id = ma.module_action_Id
                                WHERE rma.role_id = :role_id AND ma.module_id = :module_id
                                ORDER BY ma.action_id ASC";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            "role_id" => $role_id,
                            "module_id" => $module->moduleId
                        ]);
                        $role_assigned_policy = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
                    }

                    $modules[] = [
                        "moduleId" => $module->moduleId,
                        "moduleName" => $module->moduleName,
                        "moduleAssignedActions" => $module_assigned_policy,
                        "roleAssignedActions" => $role_assigned_policy
                    ];
                }

                $functionalities[] = [
                    "functionalityId" => $func->id,
                    "functionalityName" => $func->functionalityName,
                    "modules" => $modules
                ];
            }

            return ["functionalities" => $functionalities];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function savePolicy($role_id, $roles_actions_dto) {
        try {
            $role = DbHelper::findById(Roles::class, $role_id);
            if (!$role) {
                throw new Exception("Role not found");
            }

            $db = DbHelper::getDb();
            $stmt = $db->prepare("DELETE FROM `role_module_actions` WHERE `role_id` = :role_id");
            $stmt->execute(["role_id" => $role_id]);

            $functionalities = $roles_actions_dto['functionalities'] ?? [];
            foreach ($functionalities as $func_data) {
                $modules = $func_data['modules'] ?? [];
                foreach ($modules as $mod_data) {
                    $role_assigned = $mod_data['roleAssignedActions'] ?? [];
                    $module_id = $mod_data['moduleId'] ?? null;
                    foreach ($role_assigned as $action_id) {
                        $module_action = DbHelper::findFirst(
                            ModuleActions::class, 
                            "module_id = :module_id AND action_id = :action_id", 
                            ["module_id" => $module_id, "action_id" => $action_id]
                        );
                        if ($module_action) {
                            $rma = new RoleModuleActions();
                            $rma->role = $role_id;
                            $rma->moduleActions = $module_action->moduleActionId;
                            DbHelper::insert($rma);
                        }
                    }
                }
            }
            return $roles_actions_dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
