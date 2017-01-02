<?php

namespace src\RestfulRecord\behaviors;

use RestfulRecord\behaviors\ApiResponsesBehavior;
use RestfulRecord\RestfulRecord;

/**
 * @coversDefaultClass RestfulRecord\behaviors\ApiResponsesBehavior
 */
class ApiResponsesBehaviorTest extends \Codeception\TestCase\Test {

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
		$this->_behavior = new ApiResponsesBehavior();

		// Set behavior owner
		$this->_behavior->owner = $this->_mockRestfulRecord;

		// Reset responses to avoid any init'ing
		$this->_behavior->setApiResonses( array() );
	}

	/**
	 * test__GetApiResponse tests various input configurations for the getApiResponse method
	 * 
	 * @covers ::getApiResponse
	 * @covers ::addApiResponse
	 * @covers ::setApiResonses
	 */
	public function test__GetApiResponse() {
		// Test for empty response array
		verify( $this->_behavior->getApiResponse() )->false();

		// Add one response
		$this->_behavior->addApiResponse( "test" );

		// Test for both 0-index getting as well as not argument getting
		verify( $this->_behavior->getApiResponse( 0 ) )->equals( "test" );
		verify( $this->_behavior->getApiResponse() )->equals( "test" );

		// Reset responses
		$this->_behavior->setApiResonses( array(
			"foo",
			"bar",
			"baz",
		) );

		// Test for proper index getting
		verify( $this->_behavior->getApiResponse() )->notEquals( "foo" );
		verify( $this->_behavior->getApiResponse() )->equals( "baz" );
		verify( $this->_behavior->getApiResponse( 1 ) )->equals( "bar" );
	}

	/**
	 * test__GetApiResponses tests various input configurations for the getApiResponses method
	 * 
	 * @covers ::getApiResponses
	 * @covers ::addApiResponse
	 * @covers ::setApiResonses
	 */
	public function test__GetApiResponses() {
		// Test for empty responses array
		verify( $this->_behavior->getApiResponses() )->isEmpty();

		// Add one response
		$this->_behavior->addApiResponse( "test" );

		// Test array size
		verify( count( $this->_behavior->getApiResponses() ) )->equals( 1 );

		// Reset responses
		$this->_behavior->setApiResonses( array(
			"foo",
			"bar",
			"baz",
		) );

		// Test array size
		verify( count( $this->_behavior->getApiResponses() ) )->equals( 3 );
	}
}
