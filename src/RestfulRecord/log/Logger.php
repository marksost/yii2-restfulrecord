<?php

namespace RestfulRecord\log;

use yii\base\Object;
use yii\log\Logger as YiiLogger;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * Logger is a custom logging interface for logging Guzzle requests within a Yii application
 * 
 * NOTE: All requests will be logged under a category of "guzzle"
 * 
 * RestfulRecord is a Yii component that provides an ActiveRecord-like interface
 * for interacting with an API (instead of a database).
 * 
 * @package Log
 */
class Logger extends Object {

	/**
	 * @var array A map of level names to their corresponding Yii logger levels
	 */
	protected static $_levels = array(
		"error" => YiiLogger::LEVEL_ERROR,
		"warning" => YiiLogger::LEVEL_WARNING,
		"info" => YiiLogger::LEVEL_INFO,
		"trace" => YiiLogger::LEVEL_TRACE,
	);

	/**
	 * @var string The category to use when logging requests
	 */
	protected static $_category = "guzzle";

	/**
	 * log will log a message with Yii based on the level and category specified within this class
	 * NOTE: Will internally call a static version of this method
	 */
	public function log( $level, $message, array $context = array() ) {
		// Call static log method
		return static::logMessage( $level, $message, $context );
	}

	/**
	 * logMessage is a static method used for logging messages for this component
	 * using Yii's logging component
	 * NOTE: Implemented statically here so as to be called outside of just Guzzle middleware
	 * 
	 * @param string $level       The log level of the message (e.x. "notice", "info", "error", etc)
	 * @param string $message     The log message
	 * @param array $context      An array of extranious data associated with the message
	 * 
	 * @return null               Returns the response of Yii's logger's log method
	 */
	public static function logMessage( $level, $message, array $context = array() ) {
		// Get level for map with fallback
		$level = ArrayHelper::getValue( static::$_levels, $level, static::$_levels[ "info" ] );

		// Log message with Yii's logger
		return Yii::getLogger()->log( $message, $level, static::$_category );
	}
}
