<?php

namespace src\RestfulRecord;

use Codeception\Util\Stub;
use RestfulRecord\RestfulRecordComponent;

/**
 * @coversDefaultClass RestfulRecord\RestfulRecordComponent
 */
class RestfulRecordComponentTest extends \Codeception\TestCase\Test {

	// An instance of the component to be tested
	private $_component;

	// Codeception tester
	protected $tester;

	protected function _before() {
		// Set up component
		$this->_component = Stub::make( "RestfulRecord\RestfulRecordComponent" );
	}

	/**
	 * test__Init test that the init method
	 * sets up attributes
	 * 
	 * @covers ::init
	 */
	public function test__Init() {
		// Set config for later attribute setting
		$this->_component->config = array(
			"foo" => "bar",
			"test" => "one",
			"apiUrls" => "http://foo.bar,http://baz.bop",
		);

		// Call method
		$this->_component->init();

		// Verify attributes were set
		verify( isset( $this->_component->attributes[ "foo" ] ) )->true();
		verify( isset( $this->_component->attributes[ "test" ] ) )->true();
		verify( $this->_component->attributes[ "foo" ] )->equals( "bar" );
		verify( $this->_component->attributes[ "test" ] )->equals( "one" );

		// Verify API URLs were parsed
		verify( is_array( $this->_component->attributes[ "apiUrls" ] ) )->true();
		verify( in_array( $this->_component->attributes[ "apiUrl" ], $this->_component->attributes[ "apiUrls" ] ) )->true();
	}

	/**
	 * test__ParseApiUrls test that the parseApiUrls method
	 * parses out a comma-separated string into discrete URLs
	 * 
	 * @covers ::parseApiUrls
	 */
	public function test__ParseApiUrls() {
		// Verify without any URLs, an empty array is returned
		verify( $this->tester->invokeMethod( $this->_component, "parseApiUrls" ) )->equals( array() );

		// Set API URLs, all with issues to correct
		$this->_component->attributes[ "apiUrls" ] = "http://foo.bar, http://bar.baz/, , localhost.foo ";

		// Verify URLs were parsed and corrected
		verify( $this->tester->invokeMethod( $this->_component, "parseApiUrls" ) )->equals( array(
			"http://foo.bar",
			"http://bar.baz",
			"http://localhost.foo",
		) );
	}

	/**
	 * test__SelectApiUrl test that the selectApiUrl method
	 * choses a shuffled URL nd returns it
	 * 
	 * @covers ::selectApiUrl
	 */
	public function test__SelectApiUrl() {
		// Set predictable input
		$input = array(
			"http://foo.bar",
			"http://baz.bop",
			"http://localhost.foo",
		);

		// Verify an empty input returns an empty string
		verify( $this->tester->invokeMethod( $this->_component, "selectApiUrl" ) )->equals( "" );

		// Verify output is contained within input
		verify( in_array( $this->tester->invokeMethod( $this->_component, "selectApiUrl", array( $input ) ), $input ) )->true();
	}
}
