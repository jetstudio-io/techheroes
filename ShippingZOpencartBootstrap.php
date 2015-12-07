<?php
// Version
define('VERSION', '1.5.1');

// Config
require_once('config.php');
   
// Install 
if (!defined('DIR_APPLICATION')) {
	header('Location: install/index.php');
	exit;
}

// Startup
require_once(DIR_SYSTEM . 'startup.php');


// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Config
$config = new Config();
$registry->set('config', $config);

// Database 
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

if (extension_loaded('pdo') && extension_loaded('pdo_mysql') ) 
{
 	$db_pdo=new PDO("mysql:host=".DB_HOSTNAME.";dbname=".DB_DATABASE, DB_USERNAME, DB_PASSWORD);

}
else
{
	echo "The PDO system appears disabled. Please check the php.ini setting and make sure following extensions are enabled.<br>
	extension=pdo.so;<br>
	extension=pdo_mysql.so;<br>
	PDO is required.";
	exit;

}

?>