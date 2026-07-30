<?php
namespace Common\Services;

use Common\Models\UserShift;
use Exception;

class UserShiftService {

    private function _convert_model_to_dto(UserShift $user_shift) {
        return [
            "id" => $user_shift->id,
            "shiftName" => $user_shift->shiftName
        ];
    }

    public function getAllUserShift() {
        try {
            $user_shifts = DbHelper::findAll(UserShift::class, "1=1", [], "id ASC");
            $dtos = [];
            foreach ($user_shifts as $shift) {
                $dtos[] = $this->_convert_model_to_dto($shift);
            }
            return $dtos;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getUserShift($id) {
        try {
            $user_shift = DbHelper::findById(UserShift::class, $id);
            if (!$user_shift) {
                throw new Exception("Shift not found");
            }
            return $this->_convert_model_to_dto($user_shift);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function createUserShift($dto) {
        try {
            $user_shift = new UserShift();
            $user_shift->shiftName = $dto['shiftName'] ?? null;
            $user_shift = DbHelper::insert($user_shift);
            
            $dto['id'] = $user_shift->id;
            return $dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateUserShift($id, $dto) {
        try {
            $user_shift = DbHelper::findById(UserShift::class, $id);
            if (!$user_shift) {
                throw new Exception("Shift not found");
            }
            $user_shift->shiftName = $dto['shiftName'] ?? null;
            DbHelper::update($user_shift);

            $dto['id'] = $user_shift->id;
            return $dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function deleteUserShift($id) {
        try {
            $user_shift = DbHelper::findById(UserShift::class, $id);
            if (!$user_shift) {
                throw new Exception("Shift not found");
            }
            DbHelper::delete(UserShift::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
