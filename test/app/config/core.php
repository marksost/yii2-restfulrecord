<?php

// Form config array
$core = array(
	'id' => 'Restful Record',
	'name' => 'Restful Record',

	'basePath' => dirname( __DIR__ ),

	'vendorPath' => dirname( __DIR__ ).'/../../vendor',

	'components' => array(
		'cache' => array(
			'class' => 'yii\caching\ArrayCache',
		),

		'restfulrecord' => array(
			'class' => 'RestfulRecord\RestfulRecordComponent',
		),
	),

	'language' => 'en_us',
);

return $core;
