<?php

// Unit configuration overrides
$config = array(
	'controllerMap' => array(
		'fixture' => array(
			'class' => 'yii\faker\FixtureController',
			'fixtureDataPath' => '@tests/codeception/fixtures',
			'templatePath' => '@tests/codeception/templates',
			'namespace' => 'tests\codeception\fixtures',
		),
	),

	'components' => array(

	)
);

// Return test with overrides from this file
return yii\helpers\ArrayHelper::merge(
	require( __DIR__.'/../../app/config/main.php' ),
	$config
);
