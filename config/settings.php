<?php
/**
 * Application Settings and Environment Configuration
 */

// Choose Environment: 'local' or 'prod'
$env = 'demo'; // Options: local, prod

$settings = [
    'local' => [
        'db_host' => 'localhost',
        'db_port' => '3306',
        'db_user' => 'root',
        'db_pass' => '',
        'db_name' => 'calcsalary',
        
        'jwt_secret_key' => '462D4A614E645267556B58703273357638782F413F4428472B4B625065536856',
        'jwt_expiration_hours' => 10,
        
        'timesheetpro_drive' => 'C:/xampp/htdocs/calcsalary/usercontent/',
        'image_context_path' => 'http://localhost/calcsalary/usercontent/',
        
        'mail_host' => 'smtp.gmail.com',
        'mail_port' => 587,
        'mail_username' => 'webzoidsolution@gmail.com',
        'mail_password' => 'fdee tasv dsop rzwr',
        'mail_from' => 'webzoidsolution@gmail.com',
        'mail_from_name' => 'TimeSheetsPro-Support',
        
        'restrict_ips' => true,
        'allowed_client_ips' => ['127.0.0.1', '::1'],
        'allowed_client_ip_prefixes' => ['192.168.1.']
    ],
    'prod' => [
        'db_host' => '69.57.172.154',
        'db_port' => '3306',
        'db_user' => 'admin',
        'db_pass' => '01eMatrix007!',
        'db_name' => 'ematrix_calcsalary',
        
        'jwt_secret_key' => '462D4A614E645267556B58703273357638782F413F4428472B4B625065536856',
        'jwt_expiration_hours' => 10,
        
        'timesheetpro_drive' => dirname(__DIR__) . '/usercontent/',
        'image_context_path' => 'https://calcsalary.ematrixinfotech.com/api/usercontent/',
        
        'mail_host' => 'smtp.gmail.com',
        'mail_port' => 587,
        'mail_username' => 'webzoidsolution@gmail.com',
        'mail_password' => 'fdee tasv dsop rzwr',
        'mail_from' => 'webzoidsolution@gmail.com',
        'mail_from_name' => 'Calcsalary-Support',
        
        'restrict_ips' => true,
        'allowed_client_ips' => ['127.0.0.1', '::1',"150.129.166.66"],
        'allowed_client_ip_prefixes' => ['192.168.']
    ],
     'demo' => [
        'db_host' => '69.57.172.154',
        'db_port' => '3306',
        'db_user' => 'admin',
        'db_pass' => '01eMatrix007!',
        'db_name' => 'demo_calcsalary',
        
        'jwt_secret_key' => '462D4A614E645267556B58703273357638782F413F4428472B4B625065536856',
        'jwt_expiration_hours' => 10,
        
        'timesheetpro_drive' => dirname(__DIR__) . '/usercontent/',
        'image_context_path' => 'https://democalcsalary.ematrixinfotech.com/api/usercontent/',
        
        'mail_host' => 'smtp.gmail.com',
        'mail_port' => 587,
        'mail_username' => 'webzoidsolution@gmail.com',
        'mail_password' => 'fdee tasv dsop rzwr',
        'mail_from' => 'webzoidsolution@gmail.com',
        'mail_from_name' => 'Calcsalary-Support',
        
        'restrict_ips' => false,       
    ]
];

return $settings[$env];