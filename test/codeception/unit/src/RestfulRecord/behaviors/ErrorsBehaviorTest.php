<?php

namespace src\RestfulRecord\behaviors;

use \Codeception\Util\Stub;
use RestfulRecord\behaviors\ErrorsBehavior;
use RestfulRecord\RestfulRecord;
use yii\helpers\ArrayHelper;

/**
 * @coversDefaultClass RestfulRecord\behaviors\ErrorsBehavior
 */
class ErrorsBehaviorTest extends \Codeception\TestCase\Test {

	// An instance of the behavior to be tested
	private $_behavior;

	// A mock of RestfulRecord
	private $_mockRestfulRecord;

	// A predictable set of errors to use in tests
	private $_errors = array(
		"foo" => array(
			"This is a test error",
			"This is another test error",
		),
		"bar" => array(
			"This is a test error",
			"This is another test error",
		),
		"baz" => array(
			"This is a test error",
			"This is another test error",
		),
	);

	protected function _before() {
		// Set up mock
		$this->_mockRestfulRecord = new RestfulRecord();

		// Set up ID
		$this->_mockRestfulRecord->id = "foo";

		// Set up behavior
		$this->_behavior = new ErrorsBehavior();

		// Set behavior owner
		$this->_behavior->owner = $this->_mockRestfulRecord;

		// Reset errors to avoid any init'ing
		$this->_behavior->setErrors( $this->_errors );
	}

	/**
	 * test__GetErrors tests that the getErrors method returns expected data based on different types of input
	 *
	 * @covers ::getErrors
	 */
	public function test__GetErrors() {
		// Verify calling without a specific attribute returns all errors
		verify( $this->_behavior->getErrors() )->equals( $this->_errors );

		// Loop through all errors
		foreach ( $this->_errors as $key => $errors ) {
			// Verify key-based getting works
			verify( $this->_behavior->getErrors( $key ) )->equals( $errors );
		}

		// Verify an unset key returns an empty array
		verify( $this->_behavior->getErrors( "not-a-key" ) )->equals( array() );
	}

	/**
	 * test__GetFirstError tests that the getFirstError method returns expected data based on different types of input
	 *
	 * @covers ::getFirstError
	 */
	public function test__GetFirstError() {
		// Loop through all errors
		foreach ( $this->_errors as $key => $errors ) {
			// Verify key-based getting works
			verify( $this->_behavior->getFirstError( $key ) )->equals( $errors[ 0 ] );
		}

		// Verify an unset key returns false
		verify( $this->_behavior->getFirstError( "not-a-key" ) )->false();
	}

	/**
	 * test__GetFirstErrorMessageByKey tests that the getFirstErrorMessageByKey method returns an expected message,
	 * based on the result of getFirstError and the provided message key
	 *
	 * @covers ::getFirstErrorMessageByKey
	 */
	public function test__GetFirstErrorMessageByKey() {
		// Stub the behavior's getFirstError method
		$behavior = Stub::make( "RestfulRecord\behaviors\ErrorsBehavior", array(
			"getFirstError" => array(
				"foo" => "bar",
			)
		) );

		// Confirm that the message "foo" is "bar"
		verify( $behavior->getFirstErrorMessageByKey( "foo" ) )->equals( "bar" );
	}

	/**
	 * test__HasErrors tests that the hasErrors method returns expected booleans based on different types of input
	 *
	 * @covers ::hasErrors
	 */
	public function test__HasErrors() {
		// Verify calling without input returns a boolean of whether there are any errors
		// NOTE: There should be, based on the __before method setting them
		verify( $this->_behavior->hasErrors() )->true();

		// Loop through all errors
		foreach ( $this->_errors as $key => $errors ) {
			// Verify key-based checking works
			verify( $this->_behavior->hasErrors( $key ) )->true();
		}

		// Verify an unset key returns false
		verify( $this->_behavior->hasErrors( "not-a-key" ) )->false();
	}

	/**
	 * test__AddError tests that the addError method accurately sets an error
	 * in an internal array
	 * 
	 * @covers ::addError
	 */
	public function test__AddError() {
		// Reset errors
		$this->_behavior->setErrors( array() );

		// Loop through all errors
		foreach ( $this->_errors as $key => $errors ) {
			foreach ( $errors as $error ) {
				// Add each error individually
				$owner = $this->_behavior->addError( $key, $error );

				// Check that return value is the owner of the behavior
				verify( $owner )->equals( $this->_mockRestfulRecord );
			}

			// Verify adding worked
			verify( $this->_behavior->hasErrors( $key ) )->true();
		}
	}

	/**
	 * test__AddErrors tests that the addErrors method accurately sets a set of errors
	 * in an internal array
	 * 
	 * @covers ::addErrors
	 */
	public function test__AddErrors() {
		// Reset errors
		$this->_behavior->setErrors( array() );

		// Set up mock errors
		$mockErrors = ArrayHelper::merge( $this->_errors, array(
			"test" => "A non-array error",
		) );

		// Add all errors at once
		$this->_behavior->addErrors( $mockErrors );

		// Loop through all errors
		foreach ( $mockErrors as $key => $errors ) {
			// Verify errors were added correctly
			verify( $this->_behavior->getErrors( $key ) )->equals( is_array( $errors ) ? $errors : array( $errors ) );
		}
	}

	/**
	 * test__SetErrors tests that the setErrors method accurately resets all errors
	 * 
	 * @covers ::setErrors
	 */
	public function test__SetErrors() {
		// Verify errors were originally set
		verify( $this->_behavior->hasErrors() )->true();

		// Reset errors
		$owner = $this->_behavior->setErrors( array() );

		// Verify errors were unset
		verify( $this->_behavior->hasErrors() )->false();

		// Check that return value is the owner of the behavior
		verify( $owner )->equals( $this->_mockRestfulRecord );
	}

	/**
	 * test__HasErrors tests that the clearErrors method accurately clears errors
	 * based on different types of input
	 * 
	 * @covers ::clearErrors
	 */
	public function test__ClearErrors() {
		// Loop through all errors
		foreach ( $this->_errors as $key => $errors ) {
			// Clear each error by it's key
			$owner = $this->_behavior->clearErrors( $key );

			// Verify that clearing worked
			verify( $this->_behavior->hasErrors( $key ) )->false();

			// Check that return value is the owner of the behavior
			verify( $owner )->equals( $this->_mockRestfulRecord );
		}

		// Reset errors
		$this->_behavior->setErrors( $this->_errors );

		// Clear all errors
		$this->_behavior->clearErrors();

		// Verify that clearing removed all errors
		verify( $this->_behavior->hasErrors() )->false();
	}
}
