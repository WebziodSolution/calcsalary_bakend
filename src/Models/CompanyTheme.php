<?php
namespace Common\Models;

class CompanyTheme {
    public $id;
    public $companyDetails;
    public $primaryColor;
    public $sideNavigationBgColor;
    public $contentBgColor;
    public $contentBgColor2;
    public $headerBgColor;
    public $textColor;
    public $iconColor;

    public static $tableName = 'company_theme';
    public static $fieldsMap = [
        'id' => 'id',
        'companyDetails' => 'company_id',
        'primaryColor' => 'primary_color',
        'sideNavigationBgColor' => 'side_navigation_bg_color',
        'contentBgColor' => 'content_bg_color',
        'contentBgColor2' => 'content_bg_color2',
        'headerBgColor' => 'header_bg_color',
        'textColor' => 'text_color',
        'iconColor' => 'icon_color',
    ];
}
