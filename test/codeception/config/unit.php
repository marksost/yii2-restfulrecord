<?php

// Unit configuration overrides
$config = array(
	"id" => "RestfulRecord",
	"name" => "RestfulRecord",

	"basePath" => dirname( __DIR__ ),

	"vendorPath" => dirname( __DIR__ )."/../../vendor",

	"components" => array(
		"cache" => array(
			"class" => "yii\\caching\\ArrayCache",
		),

		"restfulrecord" => array(
			"class" => "RestfulRecord\\RestfulRecordComponent",
		),
	),

	"language" => "en_us",
);

// Enable all error reporting
error_reporting( E_ALL );
ini_set( "display_errors", "1" );

// Return config
return $config;
