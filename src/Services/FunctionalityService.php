<?php
namespace Common\Services;

use Common\Models\Functionality;
use Exception;

class FunctionalityService {
    
    private function _convert_model_to_dto(Functionality $f) {
        return [
            "functionalityId" => $f->id,
            "functionalityName" => $f->functionalityName
        ];
    }

    public function getAllFunctionality() {
        try {
            $items = DbHelper::findAll(Functionality::class, "1=1", [], "id ASC");
            $dtos = [];
            foreach ($items as $item) {
                $dtos[] = $this->_convert_model_to_dto($item);
            }
            return $dtos;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getFunctionality($id) {
        try {
            $f = DbHelper::findById(Functionality::class, $id);
            if (!$f) {
                throw new Exception("Functionality not found");
            }
            return $this->_convert_model_to_dto($f);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function createFunctionality($dto) {
        try {
            $f = new Functionality();
            $f->functionalityName = $dto['functionalityName'] ?? null;
            $f = DbHelper::insert($f);
            
            $dto['functionalityId'] = $f->id;
            return $dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateFunctionality($id, $dto) {
        try {
            $f = DbHelper::findById(Functionality::class, $id);
            if (!$f) {
                throw new Exception("Functionality not found");
            }
            $f->functionalityName = $dto['functionalityName'] ?? null;
            DbHelper::update($f);

            $dto['functionalityId'] = $f->id;
            return $dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function deleteFunctionality($id) {
        try {
            $f = DbHelper::findById(Functionality::class, $id);
            if (!$f) {
                throw new Exception("Functionality not found");
            }
            DbHelper::delete(Functionality::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
