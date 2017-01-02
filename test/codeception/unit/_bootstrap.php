<?php

// Get full config
$config = require( dirname( __DIR__ )."/config/unit.php" );

// Bootstrap application
new yii\web\Application( $config );
