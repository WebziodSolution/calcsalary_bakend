<?php
namespace Common\Services;

use Common\Models\CountryToState;
use Common\Serializers\CountryToStateSerializer;
use Exception;

class CountryToStateService {
    
    public function get_state_by_id($id) {
        try {
            $state = DbHelper::findById(CountryToState::class, $id);
            if (!$state) {
                throw new Exception("State not found");
            }

            $dto = new CountryToStateSerializer();
            $dto->id = $state->id;
            $dto->countryId = $state->country;
            $dto->stateLong = $state->stateLong;
            $dto->stateShort = $state->stateShort;
            $dto->stateCapital = $state->stateCapital;

            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_state() {
        try {
            $states = DbHelper::findAll(CountryToState::class, "1=1", [], "country_to_state_id ASC");
            $state_dto_list = [];
            foreach ($states as $s) {
                $state_dto_list[] = $this->get_state_by_id($s->id);
            }
            return $state_dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_state_by_country($country_id) {
        try {
            $states = DbHelper::findAll(CountryToState::class, "fk_country_id = :country_id", ["country_id" => $country_id], "country_to_state_id ASC");
            $state_dto_list = [];
            foreach ($states as $s) {
                $state_dto_list[] = $this->get_state_by_id($s->id);
            }
            return $state_dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
