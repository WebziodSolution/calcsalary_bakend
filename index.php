<?php
/**
 * Front Controller and REST API Router
 * Built in Core PHP (no dependencies).
 */

// Enable CORS
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
    header("Access-Control-Allow-Headers: " . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
} else {
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
}
header("Referrer-Policy: strict-origin-when-cross-origin");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 1. Load Autoloader
require_once __DIR__ . '/src/autoload.php';

// 2. Load Config/Settings
$config = require __DIR__ . '/config/settings.php';

use Common\Middleware\ClientIPRestrictionMiddleware;
use Common\Auth\JWTAuthentication;
use Common\Response\ApiResponse;
use Common\Exception\GlobalException;
use Common\Exception\ExceptionHandler;

try {
    // 3. Run Client IP Restriction Middleware
    ClientIPRestrictionMiddleware::handle();

    // 4. Establish Database Connection (shared instance)
    $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $db = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);

    // 5. Run JWT Authentication
    $auth = new JWTAuthentication($db);

    // Parse the Request URI Path
    $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Strip subfolder prefix if index.php is inside subfolder (e.g. /calcsalary)
    $script_name = dirname($_SERVER['SCRIPT_NAME']);
    if ($script_name !== '/' && strpos($request_uri, $script_name) === 0) {
        $request_uri = substr($request_uri, strlen($script_name));
    }

    // Standardize leading slash
    if (empty($request_uri) || $request_uri[0] !== '/') {
        $request_uri = '/' . $request_uri;
    }

    // 6. Authenticate Request (exempting public paths)
    $auth->authenticate($request_uri);

    // 7. Core Router Mapping
    // Format: 'METHOD#/pattern' => ['ControllerClass', 'methodName']
    $routes = [
        // Common
        'POST#/uploadFile/start' => ['CommonController', 'start_upload'],
        'POST#/uploadFile/chunk' => ['CommonController', 'upload_chunk'],
        'POST#/uploadFile/complete' => ['CommonController', 'complete_upload'],
        'GET#/getTimezones' => ['CommonController', 'get_timezones'],

        // CompanyActions
        'GET#/companyActions/getAllActions' => ['CompanyActionController', 'get_all_actions'],

        // CompanyDetails
        'GET#/companyDetails/search' => ['CompanyDetailsController', 'search'],
        'GET#/companyDetails/get/all' => ['CompanyDetailsController', 'get_all_company_details'],
        'GET#/companyDetails/get/(?P<id>[^/]+)' => ['CompanyDetailsController', 'get_company_details'],
        'POST#/companyDetails/create/(?P<step>[^/]+)' => ['CompanyDetailsController', 'create_company_details'],
        'PUT#/companyDetails/update/(?P<id>[^/]+)/(?P<step>[^/]+)' => ['CompanyDetailsController', 'update_company_details'],
        'DELETE#/companyDetails/delete/(?P<id>[^/]+)' => ['CompanyDetailsController', 'delete_company_details'],
        'POST#/companyDetails/uploadCompanyLogo' => ['CompanyDetailsController', 'upload_company_logo'],
        'DELETE#/companyDetails/deleteCompanyLogo/(?P<companyId>[^/]+)' => ['CompanyDetailsController', 'delete_company_logo'],
        'GET#/companyDetails/getLastCompany' => ['CompanyDetailsController', 'get_last_company'],
        'POST#/companyDetails/updateAutoTimeInAfterHours/(?P<companyId>[^/]+)' => ['CompanyDetailsController', 'update_auto_time_in_after_hours'],
        'GET#/companyDetails/getAutoTimeInAfterHours/(?P<companyId>[^/]+)' => ['CompanyDetailsController', 'get_auto_time_in_after_hours'],

        // CompanyEmployee
        'GET#/companyEmployee/getAllCompanyEmployee/(?P<companyId>\d+)' => ['CompanyEmployeeController', 'get_all_employee_by_company_id'],
        'GET#/companyEmployee/getEmployeePFAndPTReport' => ['CompanyEmployeeController', 'get_employee_pf_and_pt_report'],
        'GET#/companyEmployee/getAllEmployeeListByCompanyId/(?P<companyId>\d+)' => ['CompanyEmployeeController', 'get_all_employee_list_by_company_id'],
        'GET#/companyEmployee/get/(?P<id>\d+)' => ['CompanyEmployeeController', 'get_employee'],
        'POST#/companyEmployee/create' => ['CompanyEmployeeController', 'create_employee'],
        'PUT#/companyEmployee/update/(?P<id>\d+)' => ['CompanyEmployeeController', 'update_employee'],
        'DELETE#/companyEmployee/delete/(?P<id>\d+)' => ['CompanyEmployeeController', 'delete_employee'],
        'POST#/companyEmployee/uploadEmployeeProfile' => ['CompanyEmployeeController', 'upload_employee_profile'],
        'DELETE#/companyEmployee/deleteEmployeeImage/(?P<companyId>\d+)/(?P<employeeId>\d+)' => ['CompanyEmployeeController', 'delete_employee_image'],
        'POST#/companyEmployee/uploadEmployeeAadharImage' => ['CompanyEmployeeController', 'upload_employee_aadhar_image'],
        'DELETE#/companyEmployee/deleteEmployeeAadharImage/(?P<companyId>\d+)/(?P<employeeId>\d+)' => ['CompanyEmployeeController', 'delete_employee_aadhar_image'],
        'POST#/companyEmployee/createEmployee' => ['CompanyEmployeeController', 'create_employee_from_tsp'],
        'PUT#/companyEmployee/updateEmployee/(?P<id>\d+)' => ['CompanyEmployeeController', 'update_employee_from_tsp'],
        'GET#/companyEmployee/getLastUserId' => ['CompanyEmployeeController', 'get_last_user_id'],

        // CompanyEmployeeRole (employeeRole)
        'GET#/employeeRole/getAllRoleList' => ['CompanyEmployeeRoleController', 'get_all_roles_list'],
        'GET#/employeeRole/rolesListPage' => ['CompanyEmployeeRoleController', 'roles_list'],
        'GET#/employeeRole/getAllRoles' => ['CompanyEmployeeRoleController', 'get_all_roles'],
        'GET#/employeeRole/getActions/(?P<roleId>\d+)' => ['CompanyEmployeeRoleController', 'get_actions'],
        'GET#/employeeRole/getAllCompanyEmployeeRoles/(?P<id>\d+)' => ['CompanyEmployeeRoleController', 'get_all_company_employee_roles'],
        'GET#/employeeRole/get/(?P<id>\d+)' => ['CompanyEmployeeRoleController', 'get_employee_roles'],
        'POST#/employeeRole/create' => ['CompanyEmployeeRoleController', 'create_employee_roles'],
        'PUT#/employeeRole/update/(?P<id>\d+)' => ['CompanyEmployeeRoleController', 'update_employee_roles'],
        'DELETE#/employeeRole/delete/(?P<id>\d+)' => ['CompanyEmployeeRoleController', 'delete_employee_roles'],

        // CompanyFunctionality
        'GET#/companyFunctionality/getAllFunctionality' => ['CompanyFunctionalityController', 'get_all_functionality'],
        'GET#/companyFunctionality/get/(?P<id>[^/]+)' => ['CompanyFunctionalityController', 'get_functionality'],
        'POST#/companyFunctionality/create' => ['CompanyFunctionalityController', 'create_functionality'],
        'PUT#/companyFunctionality/update/(?P<id>[^/]+)' => ['CompanyFunctionalityController', 'update_functionality'],
        'DELETE#/companyFunctionality/delete/(?P<id>[^/]+)' => ['CompanyFunctionalityController', 'delete_functionality'],

        // CompanyModule
        'POST#/companyModule/create' => ['CompanyModuleController', 'create_module'],
        'GET#/companyModule/allModuleListPage' => ['CompanyModuleController', 'all_module_list_page'],
        'GET#/companyModule/moduleByFunctionalityListPage' => ['CompanyModuleController', 'module_by_functionality_list_page'],
        'GET#/companyModule/getAllModules' => ['CompanyModuleController', 'get_all_modules'],
        'GET#/companyModule/get/(?P<moduleId>[^/]+)' => ['CompanyModuleController', 'get_module_by_id'],
        'PUT#/companyModule/update/(?P<moduleId>[^/]+)' => ['CompanyModuleController', 'update_module_by_id'],
        'DELETE#/companyModule/delete/(?P<moduleId>[^/]+)' => ['CompanyModuleController', 'delete_module_by_id'],

        // CompanyRoleActions
        'GET#/companyRoleActions/get/all' => ['CompanyRoleActionsController', 'get_all_company_role_actions'],
        'GET#/companyRoleActions/get/(?P<id>[^/]+)' => ['CompanyRoleActionsController', 'get_action'],
        'POST#/companyRoleActions/create' => ['CompanyRoleActionsController', 'create_action'],
        'PUT#/companyRoleActions/update/(?P<id>[^/]+)' => ['CompanyRoleActionsController', 'update_action'],
        'DELETE#/companyRoleActions/delete/(?P<id>[^/]+)' => ['CompanyRoleActionsController', 'delete_action'],

        // CompanyTheme
        'GET#/companyTheme/get/all/(?P<id>\d+)' => ['CompanyThemeController', 'get_all_company_theme'],
        'GET#/companyTheme/get/(?P<id>\d+)' => ['CompanyThemeController', 'get_company_theme'],
        'POST#/companyTheme/create' => ['CompanyThemeController', 'create_company_theme'],
        'PUT#/companyTheme/update/(?P<id>\d+)' => ['CompanyThemeController', 'update_company_theme'],
        'DELETE#/companyTheme/delete/(?P<id>\d+)' => ['CompanyThemeController', 'delete_company_theme'],

        // CompanyShift
        'GET#/companyShift/get/all/(?P<companyId>\d+)' => ['CompanyShiftController', 'get_all_shifts'],
        'GET#/companyShift/get/(?P<id>\d+)' => ['CompanyShiftController', 'get_shift_by_id'],
        'POST#/companyShift/create' => ['CompanyShiftController', 'create_shift'],
        'PUT#/companyShift/update/(?P<id>\d+)' => ['CompanyShiftController', 'update_shift'],
        'DELETE#/companyShift/delete/(?P<id>\d+)' => ['CompanyShiftController', 'delete_shift'],

        // Global Actions
        'GET#/actions/getAllActions' => ['ActionController', 'get_all_actions'],

        // Global Functionality
        'GET#/functionality/getAllFunctionality' => ['FunctionalityController', 'get_all_functionality'],
        'GET#/functionality/get/(?P<id>[^/]+)' => ['FunctionalityController', 'get_functionality'],
        'POST#/functionality/create' => ['FunctionalityController', 'create_functionality'],
        'PUT#/functionality/update/(?P<id>[^/]+)' => ['FunctionalityController', 'update_functionality'],
        'DELETE#/functionality/delete/(?P<id>[^/]+)' => ['FunctionalityController', 'delete_functionality'],

        // Global Module
        'POST#/module/create' => ['ModuleController', 'create_module'],
        'GET#/module/allModuleListPage' => ['ModuleController', 'all_module_list_page'],
        'GET#/module/moduleByFunctionalityListPage' => ['ModuleController', 'module_by_functionality_list_page'],
        'GET#/module/getAllModules' => ['ModuleController', 'get_all_modules'],
        'GET#/module/get/(?P<moduleId>[^/]+)' => ['ModuleController', 'get_module_by_id'],
        'PUT#/module/update/(?P<moduleId>[^/]+)' => ['ModuleController', 'update_module_by_id'],
        'DELETE#/module/delete/(?P<moduleId>[^/]+)' => ['ModuleController', 'delete_module_by_id'],

        // Global Roles
        'GET#/roles/getAllRoleList' => ['RoleController', 'get_all_roles_list'],
        'POST#/roles/create' => ['RoleController', 'create_role'],
        'GET#/roles/rolesListPage' => ['RoleController', 'roles_list'],
        'GET#/roles/getAllRoles' => ['RoleController', 'get_all_roles'],
        'GET#/roles/get/(?P<roleId>[^/]+)' => ['RoleController', 'get_role_by_id'],
        'PUT#/roles/update/(?P<roleId>[^/]+)' => ['RoleController', 'update_role_by_id'],
        'DELETE#/roles/delete/(?P<roleId>[^/]+)' => ['RoleController', 'delete_role_by_id'],
        'GET#/roles/getActions/(?P<roleId>[^/]+)' => ['RoleController', 'get_actions'],

        // Global UserShift
        'GET#/userShift/getAllShift' => ['UserShiftController', 'get_all_shift'],
        'GET#/userShift/get/(?P<id>[^/]+)' => ['UserShiftController', 'get_user_shift'],
        'POST#/userShift/create' => ['UserShiftController', 'create_user_shift'],
        'PUT#/userShift/update/(?P<id>[^/]+)' => ['UserShiftController', 'update_user_shift'],
        'DELETE#/userShift/delete/(?P<id>[^/]+)' => ['UserShiftController', 'delete_user_shift'],

        // Global User
        'GET#/user/getAllUsers' => ['UserController', 'get_all_users'],
        'GET#/user/get/(?P<id>[^/]+)' => ['UserController', 'get_user'],
        'POST#/user/create' => ['UserController', 'create_user'],
        'PUT#/user/update/(?P<id>[^/]+)' => ['UserController', 'update_user'],
        'DELETE#/user/delete/(?P<id>[^/]+)' => ['UserController', 'delete_user'],
        'POST#/user/login' => ['UserController', 'user_login'],
        'GET#/user/generateResetLink' => ['UserController', 'generate_reset_link'],
        'GET#/user/validateToken' => ['UserController', 'validate_token'],
        'POST#/user/resetPassword' => ['UserController', 'reset_password'],
        'POST#/user/uploadProfileImage' => ['UserController', 'upload_profile_image'],
        'GET#/user/deleteProfileImage' => ['UserController', 'delete_profile_image'],

        // Global Country
        'GET#/country/get/all' => ['CountryController', 'get_all_country'],
        'GET#/country/get/(?P<id>\d+)' => ['CountryController', 'get_country'],

        // Global CountryToState
        'GET#/state/get/all' => ['CountryToStateController', 'get_all_state'],
        'GET#/state/getAllStateByCountry/(?P<id>\d+)' => ['CountryToStateController', 'get_all_state_by_country'],

        // Global Contractor
        'GET#/contractor/get/all' => ['ContractorController', 'get_all_contractors'],
        'GET#/contractor/get/(?P<id>\d+)' => ['ContractorController', 'get_contractor'],
        'POST#/contractor/create' => ['ContractorController', 'create_contractor'],
        'PUT#/contractor/update/(?P<id>\d+)' => ['ContractorController', 'update_contractor'],
        'DELETE#/contractor/delete/(?P<id>\d+)' => ['ContractorController', 'delete_contractor'],

        // Global Locations
        'GET#/location/getActiveLocations/(?P<id>\d+)' => ['LocationController', 'get_company_active_locations'],
        'GET#/location/getAllLocationByCompany/(?P<id>\d+)' => ['LocationController', 'get_all_location_by_company'],
        'GET#/location/get/all' => ['LocationController', 'get_all_location'],
        'GET#/location/getLocations' => ['LocationController', 'get_locations'],
        'GET#/location/get/(?P<id>\d+)' => ['LocationController', 'get_location'],
        'POST#/location/create' => ['LocationController', 'create_location'],
        'PUT#/location/update/(?P<id>\d+)' => ['LocationController', 'update_location'],
        'DELETE#/location/delete/(?P<id>\d+)' => ['LocationController', 'delete_location'],

        // Global LeaveType
        'GET#/leavetype/get/all/(?P<companyId>\d+)' => ['LeaveTypeController', 'get_all_leave_types'],
        'GET#/leavetype/get/(?P<id>\d+)' => ['LeaveTypeController', 'get_leave_type'],
        'POST#/leavetype/create' => ['LeaveTypeController', 'create_leave_type'],
        'PUT#/leavetype/update/(?P<id>\d+)' => ['LeaveTypeController', 'update_leave_type'],
        'DELETE#/leavetype/delete/(?P<id>\d+)' => ['LeaveTypeController', 'delete_leave_type'],

        // Global Deductions
        'GET#/deductions/get/all' => ['DeductionsController', 'get_all_deductions'],
        'GET#/deductions/get/(?P<id>\d+)' => ['DeductionsController', 'get_deductions'],
        'POST#/deductions/save' => ['DeductionsController', 'save_deductions'],
        'DELETE#/deductions/delete/(?P<id>\d+)' => ['DeductionsController', 'delete_deductions'],

        // Global Department
        'GET#/department/get/all/(?P<companyId>\d+)' => ['DepartmentController', 'get_all_department'],
        'GET#/department/get/(?P<id>\d+)' => ['DepartmentController', 'get_department'],
        'POST#/department/create' => ['DepartmentController', 'create_department'],
        'PUT#/department/update/(?P<id>\d+)' => ['DepartmentController', 'update_department'],
        'DELETE#/department/delete/(?P<id>\d+)' => ['DepartmentController', 'delete_department'],

        // Global EmployeeBankAccountInfo
        'POST#/employeeBankInfo/create' => ['EmployeeBankAccountInfoController', 'create_bank_account_info'],
        'GET#/employeeBankInfo/get/(?P<id>\d+)' => ['EmployeeBankAccountInfoController', 'get_bank_account_info_by_id'],
        'GET#/employeeBankInfo/get/all' => ['EmployeeBankAccountInfoController', 'get_all_bank_account_info'],
        'PUT#/employeeBankInfo/update/(?P<id>\d+)' => ['EmployeeBankAccountInfoController', 'update_bank_account_info'],
        'DELETE#/employeeBankInfo/delete/(?P<id>\d+)' => ['EmployeeBankAccountInfoController', 'delete_bank_account_info'],
        'POST#/employeeBankInfo/uploadPassbookImage' => ['EmployeeBankAccountInfoController', 'upload_passbook_image'],
        'DELETE#/employeeBankInfo/deletePassbookImage/(?P<companyId>\d+)/(?P<bankId>\d+)' => ['EmployeeBankAccountInfoController', 'delete_passbook_image'],

        // Global EmployeeLeaveMaster
        'GET#/employeeleavemaster/get/all/(?P<companyId>\d+)' => ['EmployeeLeaveMasterController', 'get_all_employee_leave_masters'],
        'GET#/employeeleavemaster/get/employee/(?P<employeeId>\d+)' => ['EmployeeLeaveMasterController', 'get_employee_leave_masters_by_employee'],
        'GET#/employeeleavemaster/get/(?P<id>\d+)' => ['EmployeeLeaveMasterController', 'get_employee_leave_master'],
        'POST#/employeeleavemaster/create' => ['EmployeeLeaveMasterController', 'create_employee_leave_master'],
        'PUT#/employeeleavemaster/update/(?P<id>\d+)' => ['EmployeeLeaveMasterController', 'update_employee_leave_master'],
        'DELETE#/employeeleavemaster/delete/(?P<id>\d+)' => ['EmployeeLeaveMasterController', 'delete_employee_leave_master'],

        // Global EmployeeType
        'GET#/employeeType/get/All' => ['EmployeeTypeController', 'get_all_employee_types'],
        'GET#/employeeType/get/(?P<id>\d+)' => ['EmployeeTypeController', 'get_employee_type'],
        'POST#/employeeType/create' => ['EmployeeTypeController', 'create_employee_type'],
        'PUT#/employeeType/update/(?P<id>\d+)' => ['EmployeeTypeController', 'update_employee_type'],
        'DELETE#/employeeType/delete/(?P<id>\d+)' => ['EmployeeTypeController', 'delete_employee_type'],

        // Global EmploymentInfo
        'GET#/employmentInfo/get/all' => ['EmploymentInfoController', 'get_all_employment_info'],
        'GET#/employmentInfo/get/(?P<id>\d+)' => ['EmploymentInfoController', 'get_employment_info_by_id'],
        'POST#/employmentInfo/create' => ['EmploymentInfoController', 'create_employment_info'],
        'PUT#/employmentInfo/update/(?P<id>\d+)' => ['EmploymentInfoController', 'update_employment_info'],
        'DELETE#/employmentInfo/delete/(?P<id>\d+)' => ['EmploymentInfoController', 'delete_employment_info'],

        // Global WeeklyOff
        'GET#/weekly-off/get/all/(?P<id>[^/]+)' => ['WeeklyOffController', 'get_all_by_company'],
        'GET#/weekly-off/get/(?P<id>[^/]+)' => ['WeeklyOffController', 'get_by_id'],
        'POST#/weekly-off/assignEmployees' => ['WeeklyOffController', 'assign_employees'],
        'POST#/weekly-off/create' => ['WeeklyOffController', 'create'],
        'PUT#/weekly-off/update/(?P<id>[^/]+)' => ['WeeklyOffController', 'update'],
        'DELETE#/weekly-off/delete/(?P<id>[^/]+)' => ['WeeklyOffController', 'delete'],
        'GET#/weekly-off/assignDefaultTemplate/(?P<id>[^/]+)' => ['WeeklyOffController', 'assign_default_template'],

        // Global HolidayTemplates
        'GET#/holidayTemplates/get/all/(?P<id>\d+)' => ['HolidayTemplatesController', 'get_all_holiday_templates_by_company_id'],
        'GET#/holidayTemplates/get/(?P<id>\d+)' => ['HolidayTemplatesController', 'get_holiday_template'],
        'POST#/holidayTemplates/create' => ['HolidayTemplatesController', 'create_holiday_template'],
        'PUT#/holidayTemplates/update/(?P<id>\d+)' => ['HolidayTemplatesController', 'update_holiday_template'],
        'DELETE#/holidayTemplates/delete/(?P<id>\d+)' => ['HolidayTemplatesController', 'delete_holiday_template'],
        'POST#/holidayTemplates/assignEmployees' => ['HolidayTemplatesController', 'assign_employees'],

        // Global HolidayTemplateDetails
        'GET#/holidayTemplateDetails/get/all/(?P<id>\d+)' => ['HolidayTemplateDetailsController', 'get_all_holiday_template_details_by_template_id'],
        'GET#/holidayTemplateDetails/get/(?P<id>\d+)' => ['HolidayTemplateDetailsController', 'get_holiday_template_details'],
        'POST#/holidayTemplateDetails/create' => ['HolidayTemplateDetailsController', 'create_holiday_template_details'],
        'PUT#/holidayTemplateDetails/update/(?P<id>\d+)' => ['HolidayTemplateDetailsController', 'update_holiday_template_details'],
        'DELETE#/holidayTemplateDetails/delete/(?P<id>\d+)' => ['HolidayTemplateDetailsController', 'delete_holiday_template_details'],

        // Global OvertimeRules
        'GET#/overtimerules/getAllOvertimeRules/(?P<id>[^/]+)' => ['OvertimeRulesController', 'get_all_overtime_rules'],
        'GET#/overtimerules/getOvertimeRule/(?P<id>[^/]+)' => ['OvertimeRulesController', 'get_overtime_rule'],
        'POST#/overtimerules/createOvertimeRule/(?P<id>[^/]+)' => ['OvertimeRulesController', 'create_overtime_rule'],
        'PATCH#/overtimerules/updateOvertimeRule/(?P<id>[^/]+)' => ['OvertimeRulesController', 'update_overtime_rule'],
        'DELETE#/overtimerules/deleteOvertimeRule/(?P<id>[^/]+)' => ['OvertimeRulesController', 'delete_overtime_rule']
    ];

    // Normalize route lookup by removing trailing slash
    $route_lookup = ($request_uri !== '/' && substr($request_uri, -1) === '/') ? substr($request_uri, 0, -1) : $request_uri;

    $request_method = $_SERVER['REQUEST_METHOD'];
    $route_match = null;
    $route_params = [];

    foreach ($routes as $route_key => $route_info) {
        list($m_limit, $pattern) = explode('#', $route_key, 2);
        if ($m_limit !== $request_method) {
            continue;
        }

        if (strpos($pattern, '(') !== false || strpos($pattern, '?P') !== false) {
            $regex = '#^' . $pattern . '$#';
            if (preg_match($regex, $route_lookup, $matches)) {
                $route_match = $route_info;
                foreach ($matches as $k => $v) {
                    if (is_string($k)) {
                        $route_params[$k] = $v;
                    }
                }
                break;
            }
        } else {
            if ($pattern === $route_lookup) {
                $route_match = $route_info;
                break;
            }
        }
    }

    if ($route_match) {
        list($controller_class, $method) = $route_match;
        $full_class = "Common\\Views\\$controller_class";
        $controller = new $full_class();
        
        if (!empty($route_params)) {
            call_user_func_array([$controller, $method], array_values($route_params));
        } else {
            $controller->$method();
        }
    } else {
        ApiResponse::send(404, "Endpoint not found");
    }

} catch (GlobalException $e) {
    // Replicating Spring Security raw JSON response on token error/auth fail
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode([
        "msg" => "Access Denied",
        "code" => 403,
        "status" => "failure"
    ]);
    exit;
} catch (Exception $e) {
    ExceptionHandler::handle($e);
}
