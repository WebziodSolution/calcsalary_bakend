<?php
namespace Common\Services;

use Common\Models\CompanyActions;
use Common\Serializers\CompanyActionsSerializer;
use Exception;

class CompanyRoleActionService {
    
    public function get_company_actions() {
        try {
            $company_actions = DbHelper::findAll(CompanyActions::class);
            $company_actions_dtos = [];
            foreach ($company_actions as $action) {
                $company_actions_dtos[] = $this->get_actions($action->actionId);
            }
            return $company_actions_dtos;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_actions($action_id) {
        try {
            $action = DbHelper::findById(CompanyActions::class, $action_id);
            if (!$action) {
                throw new Exception("Action not found");
            }
            
            $dto = new CompanyActionsSerializer();
            $dto->actionId = (int)$action->actionId;
            $dto->actionName = $action->actionName;
            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_actions($company_actions_dto) {
        try {
            $action = new CompanyActions();
            $action->actionName = $company_actions_dto['actionName'] ?? "";
            $action = DbHelper::insert($action);

            $dto = new CompanyActionsSerializer();
            $dto->actionId = $action->actionId;
            $dto->actionName = $action->actionName;
            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_actions($action_id, $company_actions_dto) {
        try {
            $action = DbHelper::findById(CompanyActions::class, $action_id);
            if (!$action) {
                throw new Exception("Action not found");
            }

            $action->actionName = $company_actions_dto['actionName'] ?? "";
            DbHelper::update($action);

            $dto = new CompanyActionsSerializer();
            $dto->actionId = $action->actionId;
            $dto->actionName = $action->actionName;
            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_actions($action_id) {
        try {
            $action = DbHelper::findById(CompanyActions::class, $action_id);
            if (!$action) {
                throw new Exception("Action not found");
            }
            DbHelper::delete(CompanyActions::class, $action_id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
