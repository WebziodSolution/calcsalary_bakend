<?php
namespace Common\Services;

use Common\Models\CompanyFunctionality;
use Common\Serializers\CompanyFunctionalitySerializer;
use Exception;

class CompanyFunctionalityService {
    
    public function get_all_functionality() {
        try {
            $items = DbHelper::findAll(CompanyFunctionality::class);
            $result = [];
            foreach ($items as $item) {
                $dto = new CompanyFunctionalitySerializer();
                $dto->functionalityId = $item->id;
                $dto->functionalityName = $item->functionalityName;
                $result[] = $dto;
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_functionality($functionality_id) {
        try {
            $item = DbHelper::findById(CompanyFunctionality::class, $functionality_id);
            if (!$item) {
                throw new Exception("Functionality not found");
            }
            $dto = new CompanyFunctionalitySerializer();
            $dto->functionalityId = $item->id;
            $dto->functionalityName = $item->functionalityName;
            return $dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_functionality($data) {
        try {
            $item = new CompanyFunctionality();
            $item->functionalityName = $data['functionalityName'] ?? "";
            $item = DbHelper::insert($item);

            $dto = new CompanyFunctionalitySerializer();
            $dto->functionalityId = $item->id;
            $dto->functionalityName = $item->functionalityName;
            return $dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_functionality($functionality_id, $data) {
        try {
            $item = DbHelper::findById(CompanyFunctionality::class, $functionality_id);
            if (!$item) {
                throw new Exception("Functionality not found");
            }
            $item->functionalityName = $data['functionalityName'] ?? "";
            DbHelper::update($item);

            $dto = new CompanyFunctionalitySerializer();
            $dto->functionalityId = $item->id;
            $dto->functionalityName = $item->functionalityName;
            return $dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_functionality($functionality_id) {
        try {
            $item = DbHelper::findById(CompanyFunctionality::class, $functionality_id);
            if (!$item) {
                throw new Exception("Functionality not found");
            }
            DbHelper::delete(CompanyFunctionality::class, $functionality_id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
