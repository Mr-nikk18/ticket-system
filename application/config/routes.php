<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'Auth/index';
$route['verify'] = 'Auth/index';
$route['404_override'] = '';
$route['translate_uri_dashes'] = TRUE;

/* AUTH */
$route['register']  = 'Auth/registration';
$route['login']     = 'Auth/login_check';
$route['logout']    = 'Auth/logout';
$route['register']='Auth/registration';
$route['forget_password']='Auth/fpass';
$route['check']='Auth/check_mail';
$route['reset-password/(:any)'] = 'Auth/form/$1';
$route['Modify']='Auth/Modify_pass';
/* DASHBOARD */
//$route['dashboard'] = 'TRS/dashboard';
$route['Dashboard'] = 'Dashboard/index';
$route['dashboard'] = 'Dashboard/index';
$route['qr-ticket'] = 'QrTicket/start';
$route['qr-ticket/submit'] = 'TRS/add_qr_ticket';
$route['qr-ticket/form/(:any)'] = 'TRS/qr_ticket_form/$1';
$route['qr-ticket/(:any)'] = 'QrTicket/start/$1';
$route['project-support'] = 'ProjectSupport/index';
$route['project-support/ticket-snapshot'] = 'ProjectSupport/ticket_snapshot';
$route['project-support/ai-assist'] = 'ProjectSupport/ai_assist';
$route['assets'] = 'Assets/create';
$route['assets/create'] = 'Assets/create';
$route['assets/manage'] = 'Assets/manage';
$route['assets/edit/(:num)'] = 'Assets/edit/$1';
$route['assets/update/(:num)'] = 'Assets/update/$1';
$route['assets/delete/(:num)'] = 'Assets/delete/$1';
$route['assets/next-serial'] = 'Assets/next_serial';
$route['assets/preview-qr'] = 'Assets/preview_qr';
$route['assets/store'] = 'Assets/store';
$route['assets/bulk-upload'] = 'Assets/bulk_upload';
$route['assets/store-bulk'] = 'Assets/store_bulk';
$route['assets/qr-print-center'] = 'Assets/qr_print_center';
$route['TRS/add_user'] = 'TRS/add_user_portal';
$route['TRS/save_user'] = 'TRS/save_user_portal';
$route['TRS/user_list'] = 'TRS/user_list_portal';
$route['TRS/edit_userlist/(:num)'] = 'TRS/edit_user_portal/$1';
$route['TRS/update_userlist/(:num)'] = 'TRS/update_user_portal/$1';
$route['TRS/delete_userlist/(:num)'] = 'TRS/delete_user_portal/$1';



/* TRS MODULE */
$route['see']='TRS/see';
$route['list']          = 'TRS/list';
$route['add']           = 'TRS/add';
$route['edit/(:num)']   = 'TRS/edit/$1';
$route['delete/(:num)'] = 'TRS/delete/$1';

?>
