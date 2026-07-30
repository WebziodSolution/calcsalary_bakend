<?php
namespace Common\Services;

use Common\Models\Module;
use Common\Models\Functionality;
use Common\Models\Actions;
use Common\Models\ModuleActions;
use Exception;

class ModuleService {
    
    public function get_module_policy($module_id) {
        try {
            $policies = DbHelper::findAll(ModuleActions::class, "module_id = :module_id", ["module_id" => $module_id], "action_id ASC");
            $action_ids = [];
            foreach ($policies as $p) {
                if ($p->action) {
                    $action_ids[] = (int)$p->action;
                }
            }
            return $action_ids;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function createModule($dto) {
        try {
            $func_id = $dto['functionalityId'] ?? null;
            $functionality = DbHelper::findById(Functionality::class, $func_id);
            if (!$functionality) {
                throw new Exception("Functionality not found.");
            }

            $module = new Module();
            $module->moduleName = $dto['moduleName'] ?? null;
            $module->functionality = $func_id;
            $module = DbHelper::insert($module);

            $action_ids = $dto['actions'] ?? [];
            $this->assignPolicies([
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

    public function paginate_modules($queryset_where, $params, $page, $size) {
        $db = DbHelper::getDb();
        $limit = $size > 0 ? (int)$size : 10;
        $offset = $page * $limit;

        $count_sql = "SELECT COUNT(*) FROM `module` WHERE $queryset_where";
        $count_stmt = $db->prepare($count_sql);
        $count_stmt->execute($params);
        $total_records = (int)$count_stmt->fetchColumn();
        
        $total_pages = (int)ceil($total_records / $limit);

        $sql = "SELECT * FROM `module` WHERE $queryset_where ORDER BY `module_Id` ASC LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $module_dtos = [];
        foreach ($rows as $row) {
            $module = new Module();
            foreach (Module::$fieldsMap as $prop => $col) {
                if (isset($row[$col])) {
                    $module->$prop = $row[$col];
                }
            }
            
            $func_name = "";
            if ($module->functionality) {
                $f = DbHelper::findById(Functionality::class, $module->functionality);
                $func_name = $f ? $f->functionalityName : "";
            }

            $module_dtos[] = [
                "moduleId" => $module->moduleId,
                "moduleName" => $module->moduleName,
                "functionalityId" => $module->functionality,
                "functionalityName" => $func_name,
                "actions" => $this->get_module_policy($module->moduleId)
            ];
        }

        return [
            "getTotalPages" => $total_pages,
            "getNumber" => $page,
            "getSize" => $limit,
            "getTotalRecords" => $total_records,
            "modulesList" => $module_dtos
        ];
    }

    public function allModuleListPage($search_key, $page, $size) {
        try {
            if (!empty($search_key)) {
                $where = "`module_name` LIKE :search";
                $params = ["search" => "%" . $search_key . "%"];
            } else {
                $where = "1=1";
                $params = [];
            }
            return $this->paginate_modules($where, $params, $page, $size);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function moduleByFunctionalityListPage($functionality_id, $search_key, $page, $size) {
        try {
            $where = "`functionality_id` = :func_id";
            $params = ["func_id" => $functionality_id];
            if (!empty($search_key)) {
                $where .= " AND `module_name` LIKE :search";
                $params["search"] = "%" . $search_key . "%";
            }
            return $this->paginate_modules($where, $params, $page, $size);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getAllModules() {
        try {
            $modules = DbHelper::findAll(Module::class, "1=1", [], "module_Id ASC");
            $module_dtos = [];
            foreach ($modules as $module) {
                $func_name = "";
                if ($module->functionality) {
                    $f = DbHelper::findById(Functionality::class, $module->functionality);
                    $func_name = $f ? $f->functionalityName : "";
                }

                $module_dtos[] = [
                    "moduleId" => $module->moduleId,
                    "moduleName" => $module->moduleName,
                    "functionalityId" => $module->functionality,
                    "functionalityName" => $func_name,
                    "actions" => $this->get_module_policy($module->moduleId)
                ];
            }
            return ["modulesList" => $module_dtos];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getModuleById($module_id) {
        try {
            $module = DbHelper::findById(Module::class, $module_id);
            if (!$module) {
                throw new Exception("Module not found");
            }

            $func_name = "";
            if ($module->functionality) {
                $f = DbHelper::findById(Functionality::class, $module->functionality);
                $func_name = $f ? $f->functionalityName : "";
            }

            return [
                "moduleId" => $module->moduleId,
                "moduleName" => $module->moduleName,
                "functionalityId" => $module->functionality,
                "functionalityName" => $func_name,
                "actions" => $this->get_module_policy($module->moduleId)
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateModuleById($module_id, $dto) {
        try {
            $module = DbHelper::findById(Module::class, $module_id);
            if (!$module) {
                throw new Exception("Such Module Doesn't Exist");
            }

            $func_id = $dto['functionalityId'] ?? null;
            $functionality = DbHelper::findById(Functionality::class, $func_id);
            if (!$functionality) {
                throw new Exception("Functionality not found.");
            }

            $module->moduleName = $dto['moduleName'] ?? null;
            $module->functionality = $func_id;
            DbHelper::update($module);

            $action_ids = $dto['actions'] ?? [];
            $this->assignPolicies([
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

    public function assignPolicies($assign_dto) {
        try {
            $module_id = $assign_dto['moduleId'] ?? null;
            $module = DbHelper::findById(Module::class, $module_id);
            if (!$module) {
                throw new Exception("Module not found");
            }

            // Delete existing actions
            $db = DbHelper::getDb();
            $stmt = $db->prepare("DELETE FROM `module_actions` WHERE `module_id` = :module_id");
            $stmt->execute(["module_id" => $module_id]);

            $action_ids = $assign_dto['actionIds'] ?? [];
            foreach ($action_ids as $action_id) {
                $action = DbHelper::findById(Actions::class, $action_id);
                if ($action) {
                    $ma = new ModuleActions();
                    $ma->module = $module_id;
                    $ma->action = $action_id;
                    DbHelper::insert($ma);
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function deleteModuleById($module_id) {
        try {
            $module = DbHelper::findById(Module::class, $module_id);
            if (!$module) {
                throw new Exception("Module not found");
            }
            DbHelper::delete(Module::class, $module_id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
