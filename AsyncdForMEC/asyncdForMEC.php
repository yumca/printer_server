<?php
defined('DS') || define('DS', DIRECTORY_SEPARATOR);
defined('ROOT_PATH') || define('ROOT_PATH', dirname(__FILE__));

set_include_path(get_include_path() . PATH_SEPARATOR . ROOT_PATH);

require_once('Core/Loader.php');
try {
	$daemon = Core_Daemon::getInstance();
	$daemon->init();
} catch(Exception $e) {
	Core_Base::log($e->getMessage(), 'err');
}

