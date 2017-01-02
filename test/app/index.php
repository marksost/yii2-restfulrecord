<?php

// Read in YII_DEBUG from server config or default
defined( 'YII_DEBUG' ) or define( 'YII_DEBUG', isset( $_SERVER[ 'YII_DEBUG' ] ) ? $_SERVER[ 'YII_DEBUG' ] : true );

// Read in YII_ENV from server config or default
defined( 'YII_ENV' ) or define( 'YII_ENV', isset( $_SERVER[ 'YII_ENVIRONMENT' ] ) ? $_SERVER[ 'YII_ENVIRONMENT' ] : 'dev' );

// Read in YII_TRACE_LEVEL from server config or default
defined( 'YII_TRACE_LEVEL' ) or define( 'YII_TRACE_LEVEL', isset( $_SERVER[ 'YII_TRACE_LEVEL' ] ) ? $_SERVER[ 'YII_TRACE_LEVEL' ] : 3 );

// Require autoloader and Yii
require( __DIR__.'/vendor/autoload.php' );
require( __DIR__.'/vendor/yiisoft/yii2/Yii.php' );

// Get full config
$config = require( __DIR__.'/config/main.php' );

// Bootstrap application
( new yii\web\Application( $config ) )->run();
