<?php

namespace src\RestfulRecord;

use Codeception\Util\Stub;
use RestfulRecord\RestfulRecord;
use RestfulRecord\RestfulRecordUtils;

/**
 * @coversDefaultClass RestfulRecord\RestfulRecordUtils
 */
class RestfulRecordUtilsTest extends \Codeception\TestCase\Test {

	// An instance of the utils to be tested	
	private $_utils;

	// A mock of RestfulRecord
	private $_mockRestfulRecord;

	// Codeception tester
	protected $tester;

	protected function _before() {
		// Set up mock
		$this->_mockRestfulRecord = new RestfulRecord();

		// Set up ID
		$this->_mockRestfulRecord->id = "foo";

		// Set up utils
		$this->_utils = new RestfulRecordUtils( $this->_mockRestfulRecord );
	}

	/**
	 * test__ConstructSetsRestfulRecord tests that the construct method sets up
	 * an internal RestfulRecord instance
	 * 
	 * @covers ::__construct
	 * @covers ::getRestfulRecord
	 */
	public function test__ConstructSetsRestfulRecord() {
		// Verify RestfulRecord was set
		verify( $this->_utils->getRestfulRecord() )->equals( $this->_mockRestfulRecord );
		verify( $this->_utils->getRestfulRecord()->id )->equals( "foo" );
	}

	/**
	 * test__GetRestfulRecord tests a getter for the `_restfulRecord` property
	 * 
	 * @covers ::setRestfulRecord
	 */
	public function test__SetRestfulRecord() {
		// Verify old restful record
		verify( $this->_utils->getRestfulRecord()->id )->equals( "foo" );

		// Set up new restful record
		$restfulRecord = $this->getMockForAbstractClass( "RestfulRecord\RestfulRecord" );

		// Set up ID
		$restfulRecord->id = "bar";

		// Set new restful record
		$this->tester->invokeMethod( $this->_utils, "setRestfulRecord", array( $restfulRecord ) );

		// Verify new restful record
		verify( $this->_utils->getRestfulRecord() )->equals( $restfulRecord );
		verify( $this->_utils->getRestfulRecord()->id )->equals( "bar" );
	}

	/**
	 * test__AttachClassAliases tests that the attachClassAliases method sets up all available
	 * class aliases
	 * 
	 * @covers ::attachClassAliases
	 */
	public function test__AttachClassAliases() {
		// Verify class is not aliased
		verify( class_exists( "Foo" ) )->false();

		// Set up class aliases
		$this->_utils->getRestfulRecord()->classAliases = array(
			"RestfulRecord\RestfulRecord" => "Foo",
		);

		// Attach aliases
		$this->_utils->attachClassAliases();

		// Verify class is aliased
		verify( class_exists( "Foo" ) )->true();
	}

	/**
	 * test__AttachEvents tests that the attachEvents method attaches all available events
	 * 
	 * @covers ::attachEvents
	 */
	public function test__AttachEvents() {
		// Set up mock events
		$events = array(
			"foo" => array( "foo", array( $this->_utils->getRestfulRecord(), "foo" ) ),
			"bar" => array( "bar", array( $this->_utils->getRestfulRecord(), "bar" ) ),
			"baz" => array( "baz" ),
		);

		// Set up expected results
		$expected = array(
			"foo" => true,
			"bar" => true,
			"baz" => false,
		);

		// Call attach events
		$this->_utils->attachEvents( $events );

		// Loop through mock events
		foreach ( $expected as $key => $value ) {
			if ( $value === true ) {
				// Verify handler was attached
				verify( $this->_utils->getRestfulRecord()->hasEventHandlers( $key ) )->true();				
			} else {
				// Verify handler was skipped
				verify( $this->_utils->getRestfulRecord()->hasEventHandlers( $key ) )->false();
			}
		}
	}

	/**
	 * test__FilterNullValues tests that the filterNullValues method properly filters null values
	 * as needed
	 * 
	 * @covers ::filterNullValues
	 * @dataProvider provider_FilterNullValues
	 */
	public function test__FilterNullValues( $stub, $input, $output ) {
		// Set new restful record
		$this->tester->invokeMethod( $this->_utils, "setRestfulRecord", array( $stub ) );

		// Verify filtering works
		verify( $this->_utils->filterNullValues( $input ) )->equals( $output );
	}

	/**
	 * test__FilterParams tests that the filterParams method
	 * filters out blacklisted params
	 * 
	 * @covers ::filterParams
	 */
	public function test__FilterParams() {
		//Mock restful record
		$restfulRecord = Stub::make( "RestfulRecord\RestfulRecord", array(
			"restConfig" => function() {
				return array(
					"blacklistedParams" => array( "foo", "bar", ),
				);
			},
		) );

		// Set new restful record
		$this->tester->invokeMethod( $this->_utils, "setRestfulRecord", array( $restfulRecord ) );

		// Verify input and output
		verify( $this->_utils->filterParams( array(
			"foo" => 1234,
			"test" => "two",
			"bar" => true,
			"baz" => "abcd",
		) ) )->equals( array(
			"test" => "two",
			"baz" => "abcd",
		) );
	}

	/**
	 * test__GetAllowedNullAttributes tests that the getAllowedNullAttributes returns the expected 
	 * array of attributes
	 * 
	 * @covers ::getAllowedNullAttributes
	 * @dataProvider provider_GetAllowedNullAttributes
	 */
	public function test__GetAllowedNullAttributes( $stub, $output ) {
		// Set new restful record
		$this->tester->invokeMethod( $this->_utils, "setRestfulRecord", array( $stub ) );

		// Verify filtering works
		verify( $this->tester->invokeMethod( $this->_utils, "getAllowedNullAttributes" ) )->equals( $output );
	}

	/**
	 * test__ReplaceMacros tests that the replaceMacros method replaces a pre-defined set
	 * of macros for a given input string
	 * 
	 * @covers ::replaceMacros
	 */
	public function test__ReplaceMacros() {
		//Mock restful record
		$restfulRecord = Stub::make( "RestfulRecord\RestfulRecord", array(
			"getUrlMacros" => function() {
				return array(
					":foo" => "bar",
				);
			},
		) );

		// Set new restful record
		$this->tester->invokeMethod( $this->_utils, "setRestfulRecord", array( $restfulRecord ) );

		// Verify calling replace macros without input
		verify( $this->tester->invokeMethod( $this->_utils, "replaceMacros" ) )->equals( "" );

		// Verify calling replace macros with input
		verify( $this->tester->invokeMethod( $this->_utils, "replaceMacros", array( ":foo/bar" ) ) )->equals( "bar/bar" );
	}

	/**
	 * 
	 * Begin Data Providers
	 * 
	 */

	/**
	 * provider_GetAllowedNullAttributes a stub of a restful record with some allowed null 
	 * attributes set
	 */
	public function provider_GetAllowedNullAttributes() {
		return array(
			array(
				"stub" => Stub::make( "RestfulRecord\RestfulRecord", array(
					"restConfig" => function() {
						return array(
							"allowNullValues" => false,
							"allowedNullAttributes" => array( "foo" ),
						);
					},
				) ),
				"output" => array(
					"foo"
				),
			),
		);
	}

	/**
	 * provider_FilterNullValues provides different sets of data to test various cases within
	 * the filterNullValues method
	 */
	public function provider_FilterNullValues() {
		return array(
			array(
				"stub" => Stub::make( "RestfulRecord\RestfulRecord", array(
					"restConfig" => function() {
						return array(
							"allowNullValues" => true,
						);
					},
				) ),
				"input" => array(
					1 => 2,
					"foo" => "bar",
					"baz" => null,
					"test" => array(
						3 => 4,
						5 => null,
					),
				),
				"output" => array(
					1 => 2,
					"foo" => "bar",
					"baz" => null,
					"test" => array(
						3 => 4,
						5 => null,
					),
				),
			),
			array(
				"stub" => Stub::make( "RestfulRecord\RestfulRecord", array(
					"restConfig" => function() {
						return array(
							"allowNullValues" => false,
						);
					},
				) ),
				"input" => array(
					1 => 2,
					"foo" => "bar",
					"baz" => null,
					"test" => array(
						3 => 4,
						5 => null,
					),
				),
				"output" => array(
					1 => 2,
					"foo" => "bar",
					"test" => array(
						3 => 4,
					),
				),
			),
			array(
				"stub" => Stub::make( "RestfulRecord\RestfulRecord", array(
					"restConfig" => function() {
						return array(
							"allowNullValues" => false,
							"allowedNullAttributes" => array( "baz" ),
						);
					},
				) ),
				"input" => array(
					1 => 2,
					"foo" => "bar",
					"baz" => null,
					"test" => array(
						3 => 4,
						5 => null,
					),
				),
				"output" => array(
					1 => 2,
					"foo" => "bar",
					"baz" => null,
					"test" => array(
						3 => 4,
					),
				),
			)
		);
	}
}
