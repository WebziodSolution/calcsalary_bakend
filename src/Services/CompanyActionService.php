<?php
namespace Common\Services;

use Common\Models\CompanyActions;
use Common\Serializers\CompanyActionsSerializer;
use Exception;

class CompanyActionService {
    public function get_all_actions() {
        try {
            $actions = DbHelper::findAll(CompanyActions::class);
            $result = [];
            foreach ($actions as $action) {
                $dto = new CompanyActionsSerializer();
                $dto->actionId = $action->actionId;
                $dto->actionName = $action->actionName;
                $result[] = $dto;
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
