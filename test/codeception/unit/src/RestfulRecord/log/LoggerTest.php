<?php

namespace src\RestfulRecord\log;

use \Codeception\Util\Stub;
use RestfulRecord\log\Logger;
use yii\log\Logger as YiiLogger;
use Yii;

/**
 * @coversDefaultClass RestfulRecord\log\Logger
 */
class LoggerTest extends \Codeception\TestCase\Test {
	
	// An instance of the logger to be tested
	private $_logger;

	protected function _before() {
		// Set up logger
		$this->_logger = new Logger();

		// Mock Yii logger
		Yii::setLogger( Stub::make( "yii\log\Logger", array(
			"log" => function( $message, $level, $category = "application" ) {
				// Verify this method was called
				verify( true )->true();

				// Return arguments for testing
				return array( $message, $level, $category );
			}
		) ) );
	}

	/**
	 * test__Log tests that the log method
	 * properly calls Yii's logger to log a Guzzle message
	 * 
	 * @covers ::log
	 * @covers ::logMessage
	 */
	public function test__Log() {
		// Verify method checks for invalid level
		verify( $this->_logger->log( "foo", "A test", array() ) )->equals( array(
			"A test",
			YiiLogger::LEVEL_INFO,
			"guzzle",
		) );

		// Verify method uses valid level
		verify( $this->_logger->log( "error", "A test", array() ) )->equals( array(
			"A test",
			YiiLogger::LEVEL_ERROR,
			"guzzle",
		) );

		// Verify static method checks for invalid level
		verify( Logger::logMessage( "foo", "A test", array() ) )->equals( array(
			"A test",
			YiiLogger::LEVEL_INFO,
			"guzzle",
		) );

		// Verify static method uses valid level
		verify( Logger::logMessage( "error", "A test", array() ) )->equals( array(
			"A test",
			YiiLogger::LEVEL_ERROR,
			"guzzle",
		) );
	}
}
