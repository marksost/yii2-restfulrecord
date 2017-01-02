<?php

// Set default return array
$environment = array();

// Apply specific functionality per environment
switch(YII_ENV){
	case 'dev':
		$environment = array();

		// Enable all error reporting
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
		break;
	case 'stage':
		$environment = array();
		break;
	case 'prod':
		$environment = array();
		break;
}

return $environment;
