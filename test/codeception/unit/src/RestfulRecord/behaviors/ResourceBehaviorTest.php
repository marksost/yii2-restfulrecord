<?php

namespace src\RestfulRecord\behaviors;

use RestfulRecord\behaviors\ResourceBehavior;
use RestfulRecord\RestfulRecord;

/**
 * @coversDefaultClass RestfulRecord\behaviors\ResourceBehavior
 */
class ResourceBehaviorTest extends \Codeception\TestCase\Test {
	
	// An instance of the behavior to be tested	
	private $_behavior;

	// A mock of RestfulRecord
	private $_mockRestfulRecord;

	// RestfulRecord's error key config setting
	private $_errorKey;

	// RestfulRecord's items container key config setting
	private $_itemsContainerKey;

	// A predictable set of model data to use in tests
	private $_models = array(
		array(
			"data" => array(
				"foo" => "bar",
				"test" => 1,
				"baz" => array(
					"bop",
					"fiz",
				),
			),
		),
		array(
			"data" => array(
				"foo" => "bar",
				"test" => 2,
				"baz" => array(
					"bop",
					"fiz",
				),
			),
		),
	);

	// Codeception tester
	protected $tester;

	protected function _before() {
		// Set up mock
		$this->_mockRestfulRecord = new RestfulRecord();

		// Set up ID
		$this->_mockRestfulRecord->id = "foo";

		// Set up behavior
		$this->_behavior = new ResourceBehavior();

		// Set behavior owner
		$this->_behavior->owner = $this->_mockRestfulRecord;

		// Set up error key
		$this->_errorKey = $this->_mockRestfulRecord->restConfig()[ "errorKey" ];

		// Set up items container key
		$this->_itemsContainerKey = $this->_mockRestfulRecord->restConfig()[ "itemsContainerKey" ];
	}

	/**
	 * test__CreateResourceWithoutCountKey tests that the createResource method
	 * returns null for various types of invalid input
	 * 
	 * @covers ::createResource
	 */
	public function test__CreateResourceWithoutValidInput() {
		// Verify null is returned with invalid input
		verify( $this->_behavior->createResource() )->null();
		verify( $this->_behavior->createResource( array( $this->_errorKey => "foo" ) ) )->null();
	}

	/**
	 * test__CreateResource tests that the createResource method
	 * creates a valid resource when data is properly set
	 * 
	 * @covers ::createResource
	 */
	public function test__CreateResource() {
		// Loop through all model data
		foreach ( $this->_models as $attributes ) {
			// Attempt to create resource
			$resource = $this->_behavior->createResource( $attributes );

			// Check for errors
			if ( is_null( $resource ) ) {
				$this->fail( "Expected a valid model to be created, but an error occurred in createResource" );
			}

			// Loop through all attributes
			foreach ( $attributes[ "data" ] as $key => $value ) {
				// Check resource for attribute integrity
				verify( $resource->hasAttribute( $key ) )->true();
				verify( $resource->getAttribute( $key ) )->equals( $value );
			}

			// Verify that an API response was added
			verify( $resource->getApiResponse() )->equals( $attributes );
		}
	}

	/**
	 * test__CreateCollectionWithoutValidInput tests that the createCollection method
	 * returns an empty array for various types of invalid input
	 * 
	 * @covers ::createCollection
	 */
	public function test__CreateCollectionWithoutValidInput() {
		// Verify empty array is returned with invalid input
		verify( $this->_behavior->createCollection() )->equals( array() );
		verify( $this->_behavior->createCollection( array( $this->_itemsContainerKey => array() ) ) )->equals( array() );
	}

	/**
	 * test__CreateResourceDefaultsToNullWithNonCollections tests that the createResource method
	 * returns null when called with an empty response array AND $fromCollection set to true
	 * 
	 * @covers ::createResource
	 */
	public function test__CreateResourceDefaultsToNullWithNonCollections() {
		verify( $this->_mockRestfulRecord->createResource( array(), true ) )->null();
	}

	/**
	 * test__CreateCollection tests that the createCollection method
	 * creates an array of valid resources when data is properly set
	 * 
	 * @covers ::createCollection
	 */
	public function test__CreateCollection() {
		// Form valid models array
		$models = array(
			$this->_itemsContainerKey => $this->_models,
		);

		// Create collection
		$collection = $this->_behavior->createCollection( $models );

		// Check for errors
		if ( empty( $collection ) ) {
			$this->fail( "Expected a valid set of create models, but an error occured in createCollection" );
		}

		// Loop through collection
		foreach ( $collection as $key => $resource ) {
			// Loop through all model attributes
			foreach ( $this->_models[ $key ] as $attributeKey => $value ) {
				// // Check resource for attribute integrity
				verify( $resource->hasAttribute( $attributeKey ) )->true();
				verify( $resource->getAttribute( $attributeKey ) )->equals( $value );
			}

			// Verify that an API response was added
			verify( $resource->getApiResponse() )->equals( $models );
		}
	}

	/**
	 * test__Instantiate tests that the instantiate method returns a new instance of a RestfulRecord
	 * 
	 * @covers ::instantiate
	 */
	public function test__Instantiate() {
		// Instantiate new class
		$class = $this->tester->invokeMethod( $this->_behavior, "instantiate" );

		// Verify it's a RestFulRecord
		verify( $class instanceOf RestfulRecord )->true();
	}
}
