<?php
namespace Common\Services;

use Common\Models\CompanyModules;
use Common\Models\CompanyFunctionality;
use Common\Models\CompanyActions;
use Common\Models\CompanyModuleActions;
use Exception;
use PDO;

class CompanyModuleService {
    
    public function create_module($module_dto) {
        try {
            $functionality_id = $module_dto['functionalityId'] ?? null;
            $functionality = DbHelper::findById(CompanyFunctionality::class, $functionality_id);
            if (!$functionality) {
                throw new Exception("Functionality not found.");
            }

            $module = new CompanyModules();
            $module->moduleName = $module_dto['moduleName'] ?? "";
            $module->functionality = $functionality_id;
            $module = DbHelper::insert($module);

            $action_ids = $module_dto['actions'] ?? [];
            $this->assign_policies([
                "moduleId" => $module->moduleId,
                "actionIds" => $action_ids
            ]);

            return [
                "moduleId" => $module->moduleId,
                "moduleName" => $module->moduleName,
                "functionalityId" => $functionality->id,
                "functionalityName" => $functionality->functionalityName,
                "actions" => $action_ids
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function paginate_modules($where, $params, $page, $size) {
        $db = DbHelper::getDb();
        
        $countSql = "SELECT COUNT(*) FROM company_modules WHERE " . $where;
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total_records = (int)$countStmt->fetchColumn();

        $limit = ($size > 0) ? (int)$size : 10;
        $total_pages = (int)ceil($total_records / $limit);

        $django_page_num = $page + 1;
        if ($django_page_num > $total_pages && $total_pages > 0) {
            $django_page_num = 1;
        }
        $offset = ($django_page_num - 1) * $limit;

        $modules = DbHelper::findAll(
            CompanyModules::class,
            $where,
            $params,
            "id ASC",
            "$offset, $limit"
        );

        $module_dtos = [];
        foreach ($modules as $module) {
            $functionality = DbHelper::findById(CompanyFunctionality::class, $module->functionality);
            $module_dtos[] = [
                "moduleId" => $module->moduleId,
                "moduleName" => $module->moduleName,
                "functionalityId" => $functionality ? $functionality->id : null,
                "functionalityName" => $functionality ? $functionality->functionalityName : "",
                "actions" => $this->get_module_policy($module->moduleId)
            ];
        }

        return [
            "getTotalPages" => $total_pages ?: 1,
            "getNumber" => $django_page_num - 1,
            "getSize" => $limit,
            "getTotalRecords" => $total_records,
            "modulesList" => $module_dtos
        ];
    }

    public function all_module_list_page($search_key, $page, $size) {
        try {
            $where = "1=1";
            $params = [];
            if ($search_key) {
                $where = "module_name LIKE :search_key";
                $params['search_key'] = "%" . $search_key . "%";
            }
            return $this->paginate_modules($where, $params, $page, $size);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function module_by_functionality_list_page($functionality_id, $search_key, $page, $size) {
        try {
            $where = "functionality_id = :func_id";
            $params = ["func_id" => $functionality_id];
            if ($search_key) {
                $where .= " AND module_name LIKE :search_key";
                $params['search_key'] = "%" . $search_key . "%";
            }
            return $this->paginate_modules($where, $params, $page, $size);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_modules() {
        try {
            $modules = DbHelper::findAll(CompanyModules::class, "1=1", [], "id ASC");
            $module_dtos = [];
            foreach ($modules as $module) {
                $functionality = DbHelper::findById(CompanyFunctionality::class, $module->functionality);
                $module_dtos[] = [
                    "moduleId" => $module->moduleId,
                    "moduleName" => $module->moduleName,
                    "functionalityId" => $functionality ? $functionality->id : null,
                    "functionalityName" => $functionality ? $functionality->functionalityName : "",
                    "actions" => $this->get_module_policy($module->moduleId)
                ];
            }
            return ["modulesList" => $module_dtos];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_module_by_id($module_id) {
        try {
            $module = DbHelper::findById(CompanyModules::class, $module_id);
            if (!$module) {
                throw new Exception("Module not found");
            }
            $functionality = DbHelper::findById(CompanyFunctionality::class, $module->functionality);
            return [
                "moduleId" => $module->moduleId,
                "moduleName" => $module->moduleName,
                "functionalityId" => $functionality ? $functionality->id : null,
                "functionalityName" => $functionality ? $functionality->functionalityName : "",
                "actions" => $this->get_module_policy($module->moduleId)
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_module_by_id($module_id, $module_dto) {
        try {
            $module = DbHelper::findById(CompanyModules::class, $module_id);
            if (!$module) {
                throw new Exception("Such Module Doesn't Exist");
            }

            $functionality_id = $module_dto['functionalityId'] ?? null;
            $functionality = DbHelper::findById(CompanyFunctionality::class, $functionality_id);
            if (!$functionality) {
                throw new Exception("Functionality not found.");
            }

            $module->moduleName = $module_dto['moduleName'] ?? "";
            $module->functionality = $functionality_id;
            DbHelper::update($module);

            $action_ids = $module_dto['actions'] ?? [];
            $this->assign_policies([
                "moduleId" => $module->moduleId,
                "actionIds" => $action_ids
            ]);

            return [
                "moduleId" => $module->moduleId,
                "moduleName" => $module->moduleName,
                "functionalityId" => $functionality->id,
                "functionalityName" => $functionality->functionalityName,
                "actions" => $action_ids
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function assign_policies($assign_dto) {
        try {
            $module_id = $assign_dto['moduleId'] ?? null;
            $module = DbHelper::findById(CompanyModules::class, $module_id);
            if (!$module) {
                throw new Exception("Module not found");
            }

            $db = DbHelper::getDb();
            // Delete old mappings
            $stmt = $db->prepare("DELETE FROM company_module_actions WHERE module_id = :mod_id");
            $stmt->execute(["mod_id" => $module_id]);

            $action_ids = $assign_dto['actionIds'] ?? [];
            foreach ($action_ids as $action_id) {
                $action = DbHelper::findById(CompanyActions::class, $action_id);
                if ($action) {
                    $ma = new CompanyModuleActions();
                    $ma->module = $module_id;
                    $ma->action = $action_id;
                    DbHelper::insert($ma);
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_module_by_id($module_id) {
        try {
            $module = DbHelper::findById(CompanyModules::class, $module_id);
            if (!$module) {
                throw new Exception("Module not found");
            }
            DbHelper::delete(CompanyModules::class, $module_id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_module_policy($module_id) {
        try {
            $policies = DbHelper::findAll(CompanyModuleActions::class, "module_id = :module_id", ["module_id" => $module_id], "action_id ASC");
            $actions = [];
            foreach ($policies as $p) {
                if ($p->action !== null) {
                    $actions[] = (int)$p->action;
                }
            }
            return $actions;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
