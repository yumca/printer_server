<?php

function __autoload($className)
{
    $file = str_replace("_", DS, $className) . '.php';
	//$realFile = dirname(ROOT_PATH) . '/' . $file;

    //if(file_exists($realFile)) {
        require_once($file);
    //} else {
		//require_once('Core/Exception.php');
        //throw new Core_Exception('Class "' . $className . '" could not be autoloaded in file: ' . $file);
    //}
}
