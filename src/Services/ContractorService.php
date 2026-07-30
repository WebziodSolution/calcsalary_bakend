<?php
namespace Common\Services;

use Common\Models\Contractor;
use Common\Serializers\ContractorSerializer;
use Exception;

class ContractorService {
    
    public function get_contractor($id) {
        try {
            $contractor = DbHelper::findById(Contractor::class, $id);
            if (!$contractor) {
                throw new Exception("Contractor not found");
            }

            $dto = new ContractorSerializer();
            $dto->id = $contractor->id;
            $dto->contractorName = $contractor->contractorName;

            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_contractors() {
        try {
            $contractors = DbHelper::findAll(Contractor::class, "1=1", [], "id ASC");
            $contractor_dto_list = [];
            foreach ($contractors as $c) {
                $contractor_dto_list[] = $this->get_contractor($c->id);
            }
            return $contractor_dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_contractor($contractor_dto) {
        try {
            $contractor = new Contractor();
            $contractor->contractorName = $contractor_dto['contractorName'] ?? null;
            $contractor = DbHelper::insert($contractor);
            return $this->get_contractor($contractor->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_contractor($id, $contractor_dto) {
        try {
            $contractor = DbHelper::findById(Contractor::class, $id);
            if (!$contractor) {
                throw new Exception("Contractor not found");
            }

            $contractor->contractorName = $contractor_dto['contractorName'] ?? null;
            DbHelper::update($contractor);
            return $this->get_contractor($contractor->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_contractor($id) {
        try {
            $contractor = DbHelper::findById(Contractor::class, $id);
            if (!$contractor) {
                throw new Exception("Contractor not found");
            }
            DbHelper::delete(Contractor::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
