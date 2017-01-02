<?php

namespace src\RestfulRecord\behaviors;

use RestfulRecord\behaviors\RequestBehavior;
use RestfulRecord\RestfulRecord;

/**
 * @coversDefaultClass RestfulRecord\behaviors\RequestBehavior
 */
class RequestBehaviorTest extends \Codeception\TestCase\Test {

	// An instance of the behavior to be tested	
	private $_behavior;

	// A mock of RestfulRecord
	private $_mockRestfulRecord;

	protected function _before() {
		// Set up mock
		$this->_mockRestfulRecord = new RestfulRecord();

		// Set up ID
		$this->_mockRestfulRecord->id = "foo";

		// Set up behavior
		$this->_behavior = new RequestBehavior();

		// Set behavior owner
		$this->_behavior->owner = $this->_mockRestfulRecord;
	}

	/**
	 * test__Getters tests various getters
	 * 
	 * @covers ::getBaseUrl
	 * @covers ::setBaseUrl
	 * @covers ::getData
	 * @covers ::setData
	 * @covers ::getHeaders
	 * @covers ::setHeaders
	 * @covers ::getMethod
	 * @covers ::setMethod
	 * @covers ::getParams
	 * @covers ::setParams
	 * @covers ::getPath
	 * @covers ::setPath
	 */
	public function test__GettersAndSetters() {
		$getters = array(
			"getBaseUrl" => array(
				"default" => "foo",
				"setter" => "setBaseUrl",
			),
			"getData" => array(
				"default" => array( "foo" => "bar" ),
				"setter" => "setData",
			),
			"getHeaders" => array(
				"default" => array( "foo" => "bar" ),
				"setter" => "setHeaders",
			),
			"getMethod" => array(
				"default" => "GET",
				"setter" => "setMethod",
			),
			"getParams" => array(
				"default" => array( "foo" => "bar" ),
				"setter" => "setParams",
			),
			"getPath" => array(
				"default" => "/test",
				"setter" => "setPath",
			),
		);

		foreach ( $getters as $getter => $arr ) {
			// Reset value
			$this->tester->invokeMethod( $this->_behavior, $arr[ "setter" ], array( $arr[ "default" ] ) );

			// Verify previous class object
			verify( $this->_behavior->{ $getter }() )->equals( $arr[ "default" ] );
		}
	}

	/**
	 * test__GetAndAddHeaders tests that the getHeader and addHeader methods
	 * properly manipulate the headers array
	 * 
	 * @covers ::getHeader
	 * @covers ::addHeader
	 */
	public function test__GetAndAddHeaders() {
		// Reset headers
		$this->_behavior->setHeaders( array( "foo" => "bar" ) );

		// Verify getter
		verify( $this->_behavior->getHeader( "foo" ) )->equals( "bar" );
		verify( $this->_behavior->getHeader( "not-foo" ) )->false();

		// Add header
		$this->_behavior->addHeader( "not-foo", "test" );

		// Verify getter
		verify( $this->_behavior->getHeader( "not-foo" ) )->equals( "test" );
	}

	/**
	 * test__GetAndAddHeaders tests that the getParam and addParam methods
	 * properly manipulate the headers array
	 * 
	 * @covers ::getParam
	 * @covers ::addParam
	 */
	public function test__GetAndAddParams() {
		// Reset headers
		$this->_behavior->setParams( array( "foo" => "bar" ) );

		// Verify getter
		verify( $this->_behavior->getParam( "foo" ) )->equals( "bar" );
		verify( $this->_behavior->getParam( "not-foo" ) )->false();

		// Add header
		$this->_behavior->addParam( "not-foo", "test" );

		// Verify getter
		verify( $this->_behavior->getParam( "not-foo" ) )->equals( "test" );
	}
}
