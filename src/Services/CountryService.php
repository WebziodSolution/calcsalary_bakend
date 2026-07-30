<?php
namespace Common\Services;

use Common\Models\Country;
use Common\Serializers\CountrySerializer;
use Exception;

class CountryService {
    
    public function get_country($id) {
        try {
            $country = DbHelper::findById(Country::class, $id);
            if (!$country) {
                throw new Exception("Country not found");
            }

            $dto = new CountrySerializer();
            $dto->id = $country->id;
            $dto->iso2 = $country->iso2;
            $dto->cntName = $country->cntName;
            $dto->longName = $country->longName;
            $dto->oid = $country->oid;
            $dto->cntCode = $country->cntCode;
            $dto->phoneMinLength = $country->phoneMinLength;
            $dto->phoneMaxLength = $country->phoneMaxLength;

            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_country() {
        try {
            $countries = DbHelper::findAll(Country::class, "1=1", [], "id ASC");
            $country_dto_list = [];
            foreach ($countries as $c) {
                $country_dto_list[] = $this->get_country($c->id);
            }
            return $country_dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
