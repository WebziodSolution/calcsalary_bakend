<?php
namespace Common\Services;

use Common\Models\HolidayTemplateDetails;
use Common\Models\HolidayTemplates;
use Exception;

class HolidayTemplateDetailsService {
    private $common_service;

    public function __construct() {
        $this->common_service = new CommonService();
    }

    public function get_holiday_template_details_by_id($id) {
        try {
            $entity = DbHelper::findById(HolidayTemplateDetails::class, $id);
            if (!$entity) {
                throw new Exception("Holiday Template Details not found");
            }

            $date_str = null;
            if ($entity->date) {
                $date_str = $entity->date instanceof \DateTimeInterface ? $entity->date->format("Y-m-d") : date("Y-m-d", strtotime($entity->date));
            }

            return [
                "id" => $entity->id,
                "name" => $entity->name,
                "holidayTemplateId" => $entity->holidayTemplates,
                "date" => $date_str
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_holiday_template_details_by_template_id($id) {
        try {
            $entities = DbHelper::findAll(HolidayTemplateDetails::class, "holiday_template_id = :template_id", ["template_id" => $id], "id ASC");
            $dto_list = [];
            foreach ($entities as $entity) {
                $dto_list[] = $this->get_holiday_template_details_by_id($entity->id);
            }
            return $dto_list;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_holiday_template_details($dto) {
        try {
            $template_id = $dto['holidayTemplateId'] ?? null;
            $template = DbHelper::findById(HolidayTemplates::class, $template_id);
            if (!$template) {
                throw new Exception("Holiday Template not found");
            }

            $date_val = null;
            if (!empty($dto['date'])) {
                $date_val = $this->common_service->convert_string_to_date($dto['date']);
            }

            $entity = new HolidayTemplateDetails();
            $entity->holidayTemplates = $template_id;
            $entity->name = $dto['name'] ?? null;
            $entity->date = $date_val;

            $entity = DbHelper::insert($entity);
            return $this->get_holiday_template_details_by_id($entity->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_holiday_template_details($id, $dto) {
        try {
            $entity = DbHelper::findById(HolidayTemplateDetails::class, $id);
            if (!$entity) {
                throw new Exception("Holiday Template Details not found");
            }

            $template_id = $dto['holidayTemplateId'] ?? null;
            $template = DbHelper::findById(HolidayTemplates::class, $template_id);
            if (!$template) {
                throw new Exception("Holiday Template not found");
            }

            $date_val = null;
            if (!empty($dto['date'])) {
                $date_val = $this->common_service->convert_string_to_date($dto['date']);
            }

            $entity->holidayTemplates = $template_id;
            $entity->name = $dto['name'] ?? null;
            $entity->date = $date_val;

            DbHelper::update($entity);
            return $this->get_holiday_template_details_by_id($entity->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_holiday_template_details($id) {
        try {
            $entity = DbHelper::findById(HolidayTemplateDetails::class, $id);
            if (!$entity) {
                throw new Exception("Holiday Template Details not found");
            }
            DbHelper::delete(HolidayTemplateDetails::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
