<?php
namespace Common\Services;

use Common\Models\CompanyTheme;
use Common\Models\CompanyDetails;
use Common\Serializers\CompanyThemeSerializer;
use Exception;

class CompanyThemeService {
    
    public function get_theme($id) {
        try {
            $theme = DbHelper::findById(CompanyTheme::class, $id);
            if (!$theme) {
                throw new Exception("Theme not found");
            }

            $dto = new CompanyThemeSerializer();
            $dto->id = $theme->id;
            $dto->companyId = $theme->companyDetails;
            $dto->primaryColor = $theme->primaryColor;
            $dto->sideNavigationBgColor = $theme->sideNavigationBgColor;
            $dto->contentBgColor = $theme->contentBgColor;
            $dto->contentBgColor2 = $theme->contentBgColor2;
            $dto->headerBgColor = $theme->headerBgColor;
            $dto->textColor = $theme->textColor;
            $dto->iconColor = $theme->iconColor;

            return (array)$dto;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function get_all_theme($company_id) {
        try {
            $theme = DbHelper::findFirst(CompanyTheme::class, "company_id = :company_id", ["company_id" => $company_id]);
            if (!$theme) {
                throw new Exception("Theme not found");
            }
            return $this->get_theme($theme->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function create_theme($company_theme_dto) {
        try {
            $company_id = $company_theme_dto['companyId'] ?? null;
            $company_details = DbHelper::findById(CompanyDetails::class, $company_id);
            if (!$company_details) {
                throw new Exception("Company not found");
            }

            $theme = new CompanyTheme();
            $theme->companyDetails = $company_id;
            $theme->primaryColor = $company_theme_dto['primaryColor'] ?? null;
            $theme->sideNavigationBgColor = $company_theme_dto['sideNavigationBgColor'] ?? null;
            $theme->contentBgColor = $company_theme_dto['contentBgColor'] ?? null;
            $theme->contentBgColor2 = $company_theme_dto['contentBgColor2'] ?? null;
            $theme->headerBgColor = $company_theme_dto['headerBgColor'] ?? null;
            $theme->textColor = $company_theme_dto['textColor'] ?? null;
            $theme->iconColor = $company_theme_dto['iconColor'] ?? null;

            $theme = DbHelper::insert($theme);
            return $this->get_theme($theme->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update_theme($id, $company_theme_dto) {
        try {
            $company_id = $company_theme_dto['companyId'] ?? null;
            $theme = DbHelper::findFirst(CompanyTheme::class, "company_id = :company_id", ["company_id" => $company_id]);
            if (!$theme) {
                return null;
            }

            $theme_type = $company_theme_dto['type'] ?? null;
            if ($theme_type === "setColor") {
                $theme->primaryColor = $company_theme_dto['primaryColor'] ?? $theme->primaryColor;
            } else if ($theme_type === "setSideNavigationBgColor") {
                $theme->sideNavigationBgColor = $company_theme_dto['sideNavigationBgColor'] ?? $theme->sideNavigationBgColor;
            } else if ($theme_type === "setHeaderBgColor") {
                $theme->headerBgColor = $company_theme_dto['headerBgColor'] ?? $theme->headerBgColor;
            } else if ($theme_type === "setContentBgColor") {
                $theme->contentBgColor = $company_theme_dto['contentBgColor'] ?? $theme->contentBgColor;
            } else if ($theme_type === "setIconColor") {
                $theme->iconColor = $company_theme_dto['iconColor'] ?? $theme->iconColor;
            } else if ($theme_type === "setTextColor") {
                $theme->textColor = $company_theme_dto['textColor'] ?? $theme->textColor;
            } else {
                $theme->primaryColor = $company_theme_dto['primaryColor'] ?? $theme->primaryColor;
                $theme->sideNavigationBgColor = $company_theme_dto['sideNavigationBgColor'] ?? $theme->sideNavigationBgColor;
                $theme->contentBgColor = $company_theme_dto['contentBgColor'] ?? $theme->contentBgColor;
                $theme->contentBgColor2 = $company_theme_dto['contentBgColor2'] ?? $theme->contentBgColor2;
                $theme->headerBgColor = $company_theme_dto['headerBgColor'] ?? $theme->headerBgColor;
                $theme->textColor = $company_theme_dto['textColor'] ?? $theme->textColor;
                $theme->iconColor = $company_theme_dto['iconColor'] ?? $theme->iconColor;
            }

            DbHelper::update($theme);
            return $this->get_theme($theme->id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function delete_theme($id) {
        try {
            $theme = DbHelper::findById(CompanyTheme::class, $id);
            if (!$theme) {
                throw new Exception("Theme not found");
            }
            DbHelper::delete(CompanyTheme::class, $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
