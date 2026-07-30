<?php
namespace Common\Services;

use Common\Models\Actions;
use Exception;

class ActionService {
    public function getAllActions() {
        try {
            $actions = DbHelper::findAll(Actions::class, "1=1", [], "action_Id ASC");
            $action_dtos = [];
            foreach ($actions as $action) {
                $action_dtos[] = [
                    "actionId" => $action->actionId,
                    "actionName" => $action->actionName
                ];
            }
            return $action_dtos;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
