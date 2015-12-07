<?php

# ################################################################################
# 	
#   (c) 2010, 2011, 2012 Z-Firm LLC, ALL RIGHTS RESERVED.
#   Licensed to current Stamps.com customers. 
#
#   The terms of your Stamps.com license 
#   apply to the use of this file and the contents of the  
#   Stamps_ShoppingCart_Integration_Kit__See_README_file.zip   file.
#   
#   This file is protected by U.S. Copyright. Technologies and techniques herein are
#   the proprietary methods of Z-Firm LLC. 
#  
#   For use only by customers in good standing of Stamps.com
#
#
# 	IMPORTANT
# 	=========
# 	THIS FILE IS GOVERNED BY THE STAMPS.COM LICENSE AGREEMENT
#
# 	Using or reading this file indicates your acceptance of the Stamps.com License Agreement.
#
# 	If you do not agree with these terms, this file and related files must be deleted immediately.
#
# 	Thank you for using Stamps.com!
#
################################################################################



define('AREA', 'A');


require dirname(__FILE__) . '/prepare.php';
if ( !defined('AREA') ) { die('Access denied'); }

// Require configuration
require(DIR_ROOT . '/config.php');

if (isset($_REQUEST['version'])) {
	die(PRODUCT_NAME . ': version <b>' . PRODUCT_VERSION . ' ' . PRODUCT_TYPE . (PRODUCT_STATUS != '' ? (' (' . PRODUCT_STATUS . ')') : '') . '</b>');
}

if (isset($_REQUEST['check_https'])) {
	die(defined('HTTPS') ? 'OK' : '');
}

// Include core functions/classes
require(DIR_CORE . 'db/' . $config['db_type'] . '.php');
require(DIR_CORE . 'fn.database.php');
require(DIR_CORE . 'fn.users.php');
require(DIR_CORE . 'fn.catalog.php');
require(DIR_CORE . 'fn.cms.php');
require(DIR_CORE . 'fn.cart.php');
require(DIR_CORE . 'fn.locations.php');
require(DIR_CORE . 'fn.common.php');
require(DIR_CORE . 'fn.fs.php');
require(DIR_CORE . 'fn.requests.php');
require(DIR_CORE . 'fn.images.php');
require(DIR_CORE . 'fn.init.php');
require(DIR_CORE . 'fn.control.php');
require(DIR_CORE . 'fn.search.php');
require(DIR_CORE . 'fn.promotions.php');
require(DIR_CORE . 'fn.log.php');
require(DIR_CORE . 'fn.companies.php');
if (in_array(PRODUCT_TYPE, array('PROFESSIONAL', 'MULTIVENDOR', 'MULTISHOP'))) {
    require(DIR_CORE . 'editions/fn.pro_functions.php');
}
if (in_array(PRODUCT_TYPE, array('MULTIVENDOR', 'MULTISHOP'))) {
    require(DIR_CORE . 'editions/fn.mve_functions.php');
}
if (PRODUCT_TYPE == 'MULTISHOP') {
    require(DIR_CORE . 'editions/fn.mse_functions.php');
}
fn_define('ACCOUNT_TYPE', 'customer');
if(file_exists(DIR_CORE . 'classes/profiler.php')) // ZF Case 31510
{
	require(DIR_CORE . 'classes/profiler.php');
	require(DIR_CORE . 'classes/registry.php');
	require(DIR_CORE . 'classes/session.php');

}
else
{
	require(DIR_CORE . 'class.profiler.php');
	require(DIR_CORE . 'class.registry.php');
	require(DIR_CORE . 'class.session.php');
}
// Used for the javascript to be able to hide the Loading box when a downloadable file (pdf, etc.) is ready  
//setcookie('page_unload', 'N', '0', !empty($config['current_path'])? $config['current_path'] : '/');

if (isset($_GET['ct']) && (AREA == 'A' || defined('DEVELOPMENT'))) {
	fn_rm(DIR_THUMBNAILS, false);
}

// Set configuration options from config.php to registry
Registry::set('config', $config);
unset($config);

// Check if software is installed
if (Registry::get('config.db_host') == '%DB_HOST%') {
	die(PRODUCT_NAME . ' is <b>not installed</b>. Please click here to start the installation process: <a href="install/">[install]</a>');
}
// Connect to database
$db_conn = db_initiate(Registry::get('config.db_host'), Registry::get('config.db_user'), Registry::get('config.db_password'), Registry::get('config.db_name'));
?>