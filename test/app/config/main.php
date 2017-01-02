<?php

// Include core config
$core = file_exists( dirname(__FILE__).'/core.php' ) ?
	require dirname(__FILE__).'/core.php' : array();

// Include environment config
$environment = file_exists( dirname(__FILE__).'/environment.php' ) ?
	require dirname(__FILE__).'/environment.php' : array();

// Include user override config
$userSettings = file_exists( dirname(__FILE__).'/user.settings.php' ) ?
	require dirname(__FILE__).'/user.settings.php' : array();

// Return combination of configs
return yii\helpers\ArrayHelper::merge(
	$core,
	$environment,
	$userSettings
);
