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



/* TRS MODULE */
$route['see']='TRS/see';
$route['list']          = 'TRS/list';
$route['add']           = 'TRS/add';
$route['edit/(:num)']   = 'TRS/edit/$1';
$route['delete/(:num)'] = 'TRS/delete/$1';
