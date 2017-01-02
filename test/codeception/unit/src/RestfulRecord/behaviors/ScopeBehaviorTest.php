<?php

namespace src\RestfulRecord\behaviors;

use RestfulRecord\behaviors\ScopeBehavior;
use RestfulRecord\RestfulRecord;

/**
 * @coversDefaultClass RestfulRecord\behaviors\ScopeBehavior
 */
class ScopeBehaviorTest extends \Codeception\TestCase\Test {

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
		$this->_behavior = new ScopeBehavior();

		// Set behavior owner
		$this->_behavior->owner = $this->_mockRestfulRecord;
	}

	/**
	 * test__ClearScope tests that the clearScope method
	 * clears out any currently-set scope
	 * 
	 * @covers ::clearScope
	 * @covers ::getScope
	 * @covers ::setScope
	 */
	public function test__ClearScope() {
		// Set initial state
		$result = $this->_behavior->setScope( "foo" );

		// Verify method return value
		verify( $result )->equals( $this->_mockRestfulRecord );

		// Verify state
		verify( $this->_behavior->getScope() )->equals( "foo" );

		// Call method
		$result = $this->_behavior->clearScope();

		// Verify method return value
		verify( $result )->equals( $this->_mockRestfulRecord );

		// Verify state
		verify( $this->_behavior->getScope() )->notEquals( "foo" );
		verify( $this->_behavior->getScope() )->equals( "" );
	}

	/**
	 * test__HasScope tests that the hasScope method
	 * 
	 * @covers ::hasScope
	 */
	public function test__HasScope() {
		// Set initial state
		$result = $this->_behavior->setScope( "foo" );

		// Verify method return value
		verify( $result )->equals( $this->_mockRestfulRecord );
		verify( $this->_behavior->hasScope( "foo" ) )->true();
		verify( $this->_behavior->hasScope( "bar" ) )->false();
	}

	/**
	 * test__ResetScope tests that the resetScope method
	 * 
	 * @covers ::resetScope
	 */
	public function test__ResetScope() {
		// Set initial state
		$result = $this->_behavior->setScope( "foo" );

		// Verify method return value
		verify( $result )->equals( $this->_mockRestfulRecord );

		// Verify state
		verify( $this->_behavior->getScope() )->equals( "foo" );

		// Call method
		$result = $this->_behavior->resetScope( "bar" );

		// Verify method return value
		verify( $result )->equals( $this->_mockRestfulRecord );

		// Verify state
		verify( $this->_behavior->getScope() )->notEquals( "foo" );
		verify( $this->_behavior->getScope() )->equals( "bar" );
	}
}
