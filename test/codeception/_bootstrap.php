<?php

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');

// Read in YII_DEBUG from environment or default
defined( 'YII_DEBUG' ) or define( 'YII_DEBUG', getenv( 'CORE_DEBUG' ) ?: true );

// Define yii environment as being "test"
defined( 'YII_ENV' ) or define( 'YII_ENV', 'test' );

// Read in YII_TRACE_LEVEL from server config or default
defined( 'YII_TRACE_LEVEL' ) or define( 'YII_TRACE_LEVEL', getenv( 'CORE_TRACE_LEVEL' ) ?: 3 );

// Set tests alias
Yii::setAlias( '@tests', dirname( __DIR__ ) );
