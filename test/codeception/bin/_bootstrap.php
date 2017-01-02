<?php

// Read in YII_DEBUG from environment or default
defined( 'YII_DEBUG' ) or define( 'YII_DEBUG', getenv( 'CORE_DEBUG' ) ?: true );

// Define yii environment as being "test"
defined( 'YII_ENV' ) or define( 'YII_ENV', 'test' );

// Read in YII_TRACE_LEVEL from server config or default
defined( 'YII_TRACE_LEVEL' ) or define( 'YII_TRACE_LEVEL', getenv( 'CORE_TRACE_LEVEL' ) ?: 3 );

// fcgi doesn't have STDIN and STDOUT defined by default
defined( 'STDIN' ) or define( 'STDIN', fopen( 'php://stdin', 'r' ) );
defined( 'STDOUT' ) or define( 'STDOUT', fopen( 'php://stdout', 'w' ) );

// Require autoloader and Yii
require( __DIR__.'/../../vendor/autoload.php' );
require( __DIR__.'/../../vendor/yiisoft/yii2/Yii.php' );

// Set tests alias
Yii::setAlias( '@tests', dirname( dirname( __DIR__ ) ) );
