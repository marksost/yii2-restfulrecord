<?php

namespace src\RestfulRecord;

use Codeception\Util\Stub;
use RestfulRecord\RestfulRecord;
use RestfulRecord\RestfulRecordComponent;
use RestfulRecord\RestfulRecordUtils;
use RestfulRecord\exceptions\RestfulRecordException;
use Yii;

/**
 * @coversDefaultClass RestfulRecord\RestfulRecord
 */
class RestfulRecordTest extends \Codeception\TestCase\Test {

	// An instance of the RestfulRecord to be tested
	private $_rr;

	// Codeception tester
	protected $tester;

	protected function _before() {
		// Set up RestfulRecord
		$this->_rr = Stub::make( "RestfulRecord\RestfulRecord" );
	}

	/**
	 * test__Construct tests that the __construct method
	 * properly sets up various class properties
	 * 
	 * @covers ::__construct
	 */
	public function test__Construct() {
		// Mock Restfulrecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"setIsNewResource" => function() {
				// Verify this method was called
				verify( true )->true();
			},
			"setUtils" => function() {
				// Verify this method was called
				verify( true )->true();
			},
			"setAttributes" => function() {
				// Verify this method was called
				verify( true )->true();
			},
			"init" => function() {
				// Verify this method was called
				verify( true )->true();
			},
			"attachBehaviors" => function() {
				// Verify this method was called
				verify( true )->true();
			},
			"getUtils" => function() {
				return Stub::make( "RestfulRecord\RestfulRecordUtils", array(
					"attachEvents" => function() {
						// Verify this method was called
						verify( true )->true();
					},
					"attachClassAliases" => function() {
						// Verify this method was called
						verify( true )->true();
					},
				) );
			}
		) );

		$rr->__construct();
	}

	/**
	 * test__Sleep tests that the __sleep method
	 * returns all keys as an array from the class
	 * 
	 * @covers ::__sleep
	 */
	public function test__Sleep() {
		// Verify sleep returns array keys
		verify( $this->_rr->__sleep() )->equals( array_keys( ( array )$this->_rr ) );
	}

	/**
	 * test__Get tests that the __get method
	 * checks for an internal attribute before checking class-level properties
	 * 
	 * @covers ::__get
	 */
	public function test__Get() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"id" => 1234,
			"hasAttribute" => function( $attribute ) {
				return false;
			}
		) );

		// Verify method uses parent get
		verify( $rr->__get( "id" ) )->equals( 1234 );

		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"_attributes" => array( "foo" => 2345, ),
		) );

		// Verify method uses get attribute
		verify( $rr->__get( "foo" ) )->equals( 2345 );
	}

	/**
	 * test__Set tests that the __set method
	 * properly sets an attribute
	 * 
	 * @covers ::__set
	 */
	public function test__Set() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"setAttribute" => function( $name, $value ) {
				// Verify this method was called
				verify( true )->true();
			}
		) );

		// Call method
		$rr->__set( "foo", "bar" );
	}

	/**
	 * test__Isset tests that the __isset method
	 * checks for an internal attribute before checking the parent
	 * 
	 * @covers ::__isset
	 */
	public function test__Isset() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"hasAttribute" => function( $attribute ) {
				// Verify this method was called
				verify( true )->true();

				return true;
			}
		) );

		// Call the method
		verify( $rr->__isset( "foo" ) )->true();

		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"hasAttribute" => function( $attribute ) {
				return false;
			}
		) );

		// Call the method
		verify( $rr->__isset( "foo" ) )->false();
	}

	/**
	 * test__Unset tests that the __unset method
	 * checks for an internal attribute before unsetting on the parent
	 * 
	 * @covers ::__unset
	 */
	public function test__Unset() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"hasAttribute" => function( $attribute ) {
				// Verify this method was called
				verify( true )->true();

				return true;
			},
			"unsetAttribute" => function( $attribute ) {
				// Verify this method was called
				verify( true )->true();
			}
		) );

		// Call method
		$rr->__unset( "foo" );
	}

	/**
	 * test__Behaviors tests that the behavior method
	 * properly sets various behaviors
	 *
	 * @covers ::behaviors
	 */
	public function test__Behaviors() {
		// Cache behavior keys
		$keys = array_keys( $this->_rr->behaviors() );

		// Verify behaviors contain various keys
		verify( $keys )->contains( "api-responses-behavior" );
		verify( $keys )->contains( "crud-behavior" );
		verify( $keys )->contains( "errors-behavior" );
		verify( $keys )->contains( "resource-behavior" );
		verify( $keys )->contains( "scope-behavior" );
	}

	/**
	 * test__Cast tests that the cast method
	 * returns a newly-cast model based on attributes
	 * 
	 * @covers ::cast
	 */
	public function test__Cast() {
		// Call method
		$model = $this->_rr->cast( "RestfulRecord\RestfulRecord", array( "foo" => "bar" ) );

		// Verify a model was returned
		verify( $model instanceOf RestfulRecord )->true();

		// Verify attributes were set
		verify( $model->getAttributes() )->equals( array( "foo" => "bar" ) );
	}

	/**
	 * test__CompareId tests that the compareId method
	 * checks if a model uses UUIDs or not, filters IDs when needed, and returns a boolean
	 * 
	 * @covers ::compareId
	 */
	public function test__CompareId() {
		// Mock model
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"usesUuids" => false,
			"id" => "1",
		) );

		// Verify merthod filters
		verify( $rr->compareId( 1 ) )->true();
		verify( $rr->compareId( "foo" ) )->false();

		// Mock model
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"usesUuids" => true,
			"id" => "1",
		) );

		// Verify merthod doesn't filter
		verify( $rr->compareId( 1 ) )->false();
		verify( $rr->compareId( "1" ) )->true();
	}

	/**
	 * test__Events tests that the events method
	 * properly sets various events
	 *
	 * @covers ::events
	 */
	public function test__Events() {
		// Cache event keys
		$keys = array_keys( $this->_rr->events() );

		// Verify events contain various keys
		verify( $keys )->contains( "after-construct" );
		verify( $keys )->contains( "after-delete" );
		verify( $keys )->contains( "after-find" );
		verify( $keys )->contains( "after-save" );
		verify( $keys )->contains( "before-delete" );
		verify( $keys )->contains( "before-save" );
	}

	/**
	 * test__GetComponentChecksExistingCache tests that the getComponent method
	 * checks a cache of components before loading one
	 * 
	 * @covers ::getComponent
	 */
	public function test__GetComponentChecksExistingCache() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"_component" => array(
				"foo" => "bar",
			),
			"getComponentId" => function() {
				return "foo";
			}
		) );

		// Verify an existing component is returned
		verify( $rr->getComponent() )->equals( "bar" );
	}

	/**
	 * test__GetComponentThrowsError tests that the getComponent method
	 * throws an exception when an component doesn't exist
	 * 
	 * @covers ::getComponent
	 */
	public function test__GetComponentThrowsError() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"getComponentId" => function() {
				return "test";
			}
		) );

		try {
			$rr->getComponent();
		} catch( RestfulRecordException $e ) {
			// Verify error message is properly set
			verify( $e->getMessage() )->equals( "A valid component is required for RestfulRecord to function properly." );

			return;
		}

		$this->fail( "An exception should have been throw with an invalid component." );
	}

	/**
	 * test__GetComponent tests that the getComponent method
	 * returns a component when one is found
	 * 
	 * @covers ::getComponent
	 */
	public function test__GetComponent() {
		// Verify a component is returned
		verify( $this->_rr->getComponent() instanceOf RestfulRecordComponent )->true();
	}

	/**
	 * test__Load tests that the load method
	 * checks input and sets data
	 * 
	 * @covers ::load
	 */
	public function test__Load() {
		// Verify empty data returns false
		verify( $this->_rr->load( array() ) )->false();

		// Verify non-empty data returns true
		verify( $this->_rr->load( array( "foo" => "bar", ) ) )->true();
		verify( $this->_rr->hasAttribute( "foo" ) )->true();

		// Verify array-searching
		verify( $this->_rr->load( array( "test" => array( "baz" => "bop" ) ), "test" ) )->true();
		verify( $this->_rr->hasAttribute( "test" ) )->false();
		verify( $this->_rr->hasAttribute( "baz" ) )->true();
	}

	/**
	 * test__ModelChecksExistingCache tests that the model method
	 * checks a cache before making a new model
	 * 
	 * @covers ::model
	 */
	public function test__ModelChecksExistingCache() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord" );

		// Set model cache
		$rr = Stub::update( $rr, array(
			"_models" => array(
				get_class( $rr ) => "foo",
			),
		) );

		// Verify method checks cache first
		verify( $rr::model() )->equals( "foo" );
	}

	/**
	 * test__Model tests that the model method
	 * create a new model and stores it in cache before returning it
	 * 
	 * @covers ::model
	 */
	public function test__Model() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord" );

		// Unset model cache
		$this->tester->reflectProperty( $rr, "_models" )->setValue( $rr, array() );

		// Verify a model is returned
		verify( $rr::model() instanceOf RestfulRecord )->true();
		verify( array_keys( $this->tester->reflectProperty( $rr, "_models" )->getValue( $rr ) ) )->contains( get_class( $rr ) );
	}

	/**
	 * test__ModelFactory tests that the modelFactory method returns a new model based on input
	 *
	 * @covers ::modelFactory
	 */
	public function test__ModelFactory() {
		// Attempt to create model
		$model = $this->_rr->modelFactory( "RestfulRecord\RestfulRecord" );

		// Verify a model was returned
		verify( $model instanceOf RestfulRecord )->true();
	}

	/**
	 * test__UsesUuid tests that the usesUuid method
	 * properly checks a class static variable
	 * 
	 * @covers ::usesUuid
	 */
	public function test__UsesUuid() {
		// Mock model
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"usesUuids" => false,
			"id" => "1",
		) );

		// Verify model property
		verify( $rr::usesUuid() )->false();
	}

	/**
	 * test__GetAttribute tests that the getAttribute method
	 * returns an attribute if it exists
	 * 
	 * @covers ::getAttribute
	 */
	public function test__GetAttribute() {
		// Verify the method checks class-level properties first
		verify( $this->_rr->getAttribute( "_attributes" ) )->equals( array() );

		// Set attribute
		$this->_rr->setAttribute( "foo", "bar" );

		// Verify attributes are checked after class-level properties
		verify( $this->_rr->getAttribute( "foo" ) )->equals( "bar" );

		// Verify unset attributes return false
		verify( $this->_rr->getAttribute( "test" ) )->false();
	}

	/**
	 * test__GetAttributes tests that the getAttributes method
	 * return an array of attributes
	 * 
	 * @covers ::getAttributes
	 */
	public function test__GetAttributes() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"_attributes" => array(
				"foo" => "bar",
				"baz" => "bop",
				"test" => "one",
			),
		) );

		// Verify attributes are returned
		verify( $rr->getAttributes() )->equals( array(
			"foo" => "bar",
			"baz" => "bop",
			"test" => "one",
		) );

		// Verify attributes are filtered
		verify( $rr->getAttributes( array( "foo", "test", "_new", "new-key" )) )->equals( array(
			"foo" => "bar",
			"test" => "one",
			"_new" => false,
			"new-key" => null,
		) );
	}

	/**
	 * test__GetAttributesToSend tests that the getAttributesToSend method
	 * returns an array of filtered attributes
	 * 
	 * @covers ::getAttributesToSend
	 */
	public function test__GetAttributesToSend() {
		// Mock RestuflRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"getAttributes" => function() {
				// Verify this method was called
				verify( true )->true();

				return array();
			},
			"getUtils" => function() {
				return Stub::make( "RestfulRecord\RestfulRecordUtils", array(
					"filterNullValues" => function( $values ) {
						return "foo";
					}
				) );
			}
		) );

		// Verify method calls a utility method
		verify( $this->tester->invokeMethod( $rr, "getAttributesToSend", array( "foo", "bar", ) ) )->equals( "foo" );
	}

	/**
	 * test__HasAttribute tests that the hasAttribute method
	 * checks for an attribute
	 * 
	 * @covers ::hasAttribute
	 */
	public function test__HasAttribute() {
		// Mock Restfulrecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"_attributes" => array(
				"foo" => "bar",
			)
		) );

		// Verify method checks attributes
		verify( $rr->hasAttribute( "foo" ) )->true();
		verify( $rr->hasAttribute( "test" ) )->false();
	}

	/**
	 * test__SetAttribute tests that the setAttribute method
	 * sets an attribute
	 * 
	 * @covers ::setAttribute
	 */
	public function test__SetAttribute() {
		// Call method
		$this->_rr->setAttribute( "_new", true );

		// Verify method set class-level properties
		verify( $this->_rr->getAttribute( "_new" ) )->true();

		// Call method
		$this->_rr->setAttribute( "test", "one" );

		// Verify attributes are set
		verify( $this->_rr->hasAttribute( "test" ) )->true();
		verify( $this->_rr->getAttribute( "test" ) )->equals( "one" );
	}

	/**
	 * test__SetAttributes tests that the setAttributes method
	 * sets an array of attributes
	 * 
	 * @covers ::setAttributes
	 */
	public function test__SetAttributes() {
		// Call method
		$result = $this->_rr->setAttributes( array(
			"foo" => "bar",
		) );

		// Verify attributes were set
		verify( $this->_rr->hasAttribute( "foo" ) )->true();

		// Verify model was returned
		verify( $result )->equals( $this->_rr );
	}

	/**
	 * test__UnsetAttribute tests that the unsetAttribute method
	 * unsets an attribute
	 * 
	 * @covers ::unsetAttribute
	 */
	public function test__UnsetAttribute() {
		// Mock Restfulrecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"_attributes" => array(
				"foo" => "bar",
			)
		) );

		// Call method
		$rr->unsetAttribute( "test" );
		$rr->unsetAttribute( "foo" );

		// Verify method unsets an attribute if it exists
		verify( $rr->hasAttribute( "foo" ) )->false();
	}

	/**
	 * test__GetAndSetUtils tests the getComponentId and setComponentId methods
	 * to check that an internal value is properly set
	 * 
	 * @covers ::getComponentId
	 * @covers ::setComponentId
	 */
	public function test__GetAndSetComponentId() {
		// Reset value
		$this->tester->invokeMethod( $this->_rr, "setComponentId", array( "foo" ) );

		// Verify previous class object
		verify( $this->_rr->getComponentId() )->equals( "foo" );
	}

	/**
	 * test__GetIdThrowsException tests that the getId method
	 * throws an exception when an ID property doesn't exist
	 * 
	 * @covers ::getId
	 */
	public function test__GetIdThrowsException() {
		// Mock restfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"restConfig" => function() {
				return array( "idProperty" => "foo" );
			},
		) );

		try {
			$rr->getId();
		} catch( RestfulRecordException $e ) {
			// Verify error message is properly set
			verify( $e->getMessage() )->equals( "An idProperty must be set and available for RestfulRecord to function properly." );

			return;
		}

		$this->fail( "An exception should have been throw with an invalid ID." );
	}

	/**
	 * test__GetId tests that the getId method
	 * returns a model's ID
	 * 
	 * @covers ::getId
	 */
	public function test__GetId() {
		// Set ID
		$this->_rr->id = 1234;

		// Verify the ID is returned
		verify( $this->_rr->getId() )->equals( 1234 );
	}

	/**
	 * test__GetIdProperty tests that the getIdProperty method
	 * returns rest config's id property value
	 * 
	 * @covers ::getIdProperty
	 */
	public function test__GetIdProperty() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"restConfig" => function() {
				return array( "idProperty" => "foo" );
			},
		) );

		// Verify the ID property is properly returned
		verify( $rr->getIdProperty() )->equals( "foo" );
	}

	/**
	 * test__GetAndSetUtils tests the getUtils and setUtils methods
	 * to check that an internal value is properly set
	 * 
	 * @covers ::getUtils
	 * @covers ::setUtils
	 */
	public function test__GetAndSetUtils() {
		// Store utils
		$utils = new RestfulRecordUtils( $this->_rr );

		// Reset value
		$this->tester->invokeMethod( $this->_rr, "setUtils", array( $utils ) );

		// Verify previous class object
		verify( $this->_rr->getUtils() )->equals( $utils );
	}

	/**
	 * test__IsAndSetNewResource tests the isNewResource and setIsNewResource methods
	 * to check that an internal value is properly set
	 * 
	 * @covers ::isNewResource
	 * @covers ::setIsNewResource
	 */
	public function test__IsAndSetNewResource() {
		// Reset value
		$this->_rr->setIsNewResource( true );

		// Verify previous class object
		verify( $this->_rr->isNewResource() )->true();
	}

	/**
	 * test__FindAllCheckCollection tests that the findAll method
	 * checks checks the get status of a collection of models
	 * 
	 * @covers ::findAll
	 */
	public function test__FindAllCheckCollection() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"crud-behavior" => Stub::make( "RestfulRecord\behaviors\CrudBehavior", array(
						"get" => function( $route = "", array $params = array() ) {
							return null;
						}
					) ),
					"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
					"resource-behavior" => Stub::make( "RestfulRecord\behaviors\ResourceBehavior", array(
						"createCollection" => function( $response = array() ) {
							return null;
						}
					) ),
				);
			},
		) );

		// Verify a null response returns an empty array
		verify( $rr->findAll() )->equals( array() );
	}

	/**
	 * test__FindAll tests that the findAll method
	 * attempts to find an array of models
	 * 
	 * @covers ::findAll
	 */
	public function test__FindAll() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"cache-behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
						"setResponseInCache" => function() {
							// Verify this method was called
							verify( true )->true();
						}
					) ),
					"crud-behavior" => Stub::make( "RestfulRecord\behaviors\CrudBehavior", array(
						"get" => function( $route = "", array $params = array() ) {
							return array(
								array( "foo" => "bar", ),
								array( "test" => "one", ),
							);
						}
					) ),
					"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
					"resource-behavior" => Stub::make( "RestfulRecord\behaviors\ResourceBehavior", array(
						"createCollection" => function( $response = array() ) {
							$temp = array();

							foreach ( $response as $arr ) {
								$temp[] = ( new RestfulRecord() )->setAttributes( $arr );
							}

							return $temp;
						}
					) ),
				);
			},
		) );

		// Store result
		$result = $rr->findAll();

		// Verify array was returned
		verify( count( $result ) )->equals( 2 );

		// Verify an array of models was returned
		foreach ( $result as $model ) {
			verify( $model instanceOf RestfulRecord )->true();

			// Verify newness
			verify( $model->isNewResource() )->false();
		}
	}

	/**
	 * test__FindAllInCacheChecksResponseIsInCache tests that the findAllInCache method
	 * checks for a response in cache
	 * 
	 * @covers ::findAllInCache
	 */
	public function test__FindAllInCacheChecksResponseIsInCache() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"_utils" => Stub::make( "RestfulRecord\RestfulRecordUtils", array(
				"replaceMacros" => "",
			) ),
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"cache-behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
						"cache" => function() {
							// Verify this method was called
							verify( true )->true();
						},
						"configureCache" => function() {
							// Verify this method was called
							verify( true )->true();
						},
						"getResponseFromCache" => false,
					) ),
					"crud-behavior" => "RestfulRecord\behaviors\CrudBehavior",
					"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
					"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
				);
			},
		) );

		// Verify an empty array was returned
		verify( $rr->findAllInCache() )->equals( array() );
	}

	/**
	 * test__FindAllInCacheCallsFindAllWhenResponseIsInCache tests that the findAllInCache method
	 * calls `findAll` if a response is in cache
	 * 
	 * @covers ::findAllInCache
	 */
	public function test__FindAllInCacheCallsFindAllWhenResponseIsInCache() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"_utils" => Stub::make( "RestfulRecord\RestfulRecordUtils", array(
				"replaceMacros" => "",
			) ),
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"cache-behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
						"cache" => function() {
							// Verify this method was called
							verify( true )->true();
						},
						"configureCache" => function() {
							// Verify this method was called
							verify( true )->true();
						},
						"getResponseFromCache" => true,
					) ),
					"crud-behavior" => "RestfulRecord\behaviors\CrudBehavior",
					"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
					"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
				);
			},
			"findAll" => "findAll",
		) );

		// Verify the result of `findAll` was returned
		verify( $rr->findAllInCache() )->equals( "findAll" );
	}

	/**
	 * test__FindByIdChecksResource tests that the findById method
	 * checks input and get status of a model
	 * 
	 * @covers ::findById
	 */
	public function test__FindByIdChecksResource() {
		// Verify an empty input returns null
		verify( $this->_rr->findById( 0 ) )->null();

		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"crud-behavior" => Stub::make( "RestfulRecord\behaviors\CrudBehavior", array(
						"get" => function( $route = "", array $params = array() ) {
							return null;
						}
					) ),
					"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
					"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
				);
			},
		) );

		// Verify a null response returns null
		verify( $rr->findById( 1234 ) )->null();
	}

	/**
	 * test__FindById tests that the findById method
	 * attempts to find a model
	 * 
	 * @covers ::findById
	 */
	public function test__FindById() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"cache-behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
						"setResponseInCache" => function() {
							// Verify this method was called
							verify( true )->true();
						}
					) ),
					"crud-behavior" => Stub::make( "RestfulRecord\behaviors\CrudBehavior", array(
						"get" => function( $route = "", array $params = array() ) {
							return array( "foo" => "bar", );
						}
					) ),
					"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
					"resource-behavior" => Stub::make( "RestfulRecord\behaviors\ResourceBehavior", array(
						"createResource" => function( $response = array(), $fromCollection = false ) {
							return ( new RestfulRecord() )->setAttributes( $response );
						}
					) ),
				);
			},
		) );

		// Store result
		$result = $rr->findById( 1234 );

		// Verify a null response returns null
		verify( $result->getAttributes() )->equals( array( "foo" => "bar", ) );

		// Verify newness
		verify( $result->isNewResource() )->false();
	}

	/**
	 * test__FindByUuid tests that the findByUuid method
	 * proxies to the findById method
	 * 
	 * @covers ::findByUuid
	 */
	public function test__FindByUuid() {
		// Mock model
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"findById" => function( $id, array $params = array() ) {
				// Verify this method was called
				verify( true )->true();

				return "foo";
			},
		) );

		// Verify return value
		verify( $rr->findByUuid( 1234 ) )->equals( "foo" );
	}

	/**
	 * test__FindOne tests that the findOne method
	 * chooses the right method to use for finding a model
	 * 
	 * @covers ::findOne
	 */
	public function test__FindOne() {
		// Mock model
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"usesUuids" => false,
			"findById" => function( $id, array $params = array() ) {
				// Verify this method was called
				verify( true )->true();

				return $id;
			},
		) );

		// Verify method detects static variable, chooses method, and filters ID if needed
		verify( $rr->findOne( "1234" ) === 1234 )->true();

		// Mock model
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"usesUuids" => true,
			"findByUuid" => function( $id, array $params = array() ) {
				// Verify this method was called
				verify( true )->true();

				return $id;
			},
		) );

		// Verify method detects static variable, chooses method, and filters ID if needed
		verify( $rr->findOne( "1234" ) === "1234" )->true();
	}

	/**
	 * test__FindOneInCacheChecksResponseIsInCache tests that the findOneInCache method
	 * checks for a response in cache
	 * 
	 * @covers ::findOneInCache
	 */
	public function test__FindOneInCacheChecksResponseIsInCache() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"_utils" => Stub::make( "RestfulRecord\RestfulRecordUtils", array(
				"replaceMacros" => "",
			) ),
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"cache-behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
						"cache" => function() {
							// Verify this method was called
							verify( true )->true();
						},
						"configureCache" => function() {
							// Verify this method was called
							verify( true )->true();
						},
						"getResponseFromCache" => false,
					) ),
					"crud-behavior" => "RestfulRecord\behaviors\CrudBehavior",
					"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
					"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
				);
			},
		) );

		// Verify null was returned
		verify( $rr->findOneInCache( "foo" ) )->null();
	}

	/**
	 * test__FindOneInCacheCallsFindAllWhenResponseIsInCache tests that the findOneInCache method
	 * calls `findOne` if a response is in cache
	 * 
	 * @covers ::findOneInCache
	 */
	public function test__FindOneInCacheCallsFindAllWhenResponseIsInCache() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"_utils" => Stub::make( "RestfulRecord\RestfulRecordUtils", array(
				"replaceMacros" => "",
			) ),
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"cache-behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
						"cache" => function() {
							// Verify this method was called
							verify( true )->true();
						},
						"configureCache" => function() {
							// Verify this method was called
							verify( true )->true();
						},
						"getResponseFromCache" => true,
					) ),
					"crud-behavior" => "RestfulRecord\behaviors\CrudBehavior",
					"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
					"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
				);
			},
			"findOne" => "findOne",
		) );

		// Verify the result of `findOne` was returned
		verify( $rr->findOneInCache( "foo" ) )->equals( "findOne" );
	}

	/**
	 * test__UpdateByIdChecksClassAttributes tests that the updateById method
	 * checks various class attributes before attempting to update the model
	 * 
	 * @covers ::updateById
	 */
	public function test__UpdateByIdChecksClassAttributes() {
		// Verify an empty input returns false
		verify( $this->_rr->updateById( 0 ) )->false();

		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"beforeSave" => function() {
				return false;
			}
		) );

		// Verify method checks beforeSave
		verify( $rr->updateById( 1234 ) )->false();
	}

	/**
	 * test__UpdateByIdChecksForSuccessInUpdate tests that the updateById method
	 * checks if a model was successfully updated
	 * 
	 * @covers ::updateById
	 */
	public function test__UpdateByIdChecksForSuccessInUpdate() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"crud-behavior" => Stub::make( "RestfulRecord\behaviors\CrudBehavior", array(
						"put" => function( $route = "", array $data = null, array $params = array(), $method = "PATCH" ) {
							return null;
						}
					) ),
					"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
					"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
				);
			},
			"beforeSave" => function() {
				return true;
			},
		) );

		// Verify models with non-success in updating returns false
		verify( $rr->updateById( 1234 ) )->false();
	}

	/**
	 * test__UpdateByIdChecksRefresh tests that the updateById method
	 * checks a refresh value
	 * 
	 * @covers ::updateById
	 */
	public function test__UpdateByIdChecksRefresh() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"crud-behavior" => Stub::make( "RestfulRecord\behaviors\CrudBehavior", array(
						"put" => function( $route = "", array $data = null, array $params = array(), $method = "PATCH" ) {
							return "foo";
						}
					) ),
					"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
					"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
				);
			},
			"beforeSave" => function() {
				return true;
			},
			"refresh" => function() {
				// Verify this method was called
				verify( true )->true();
			}
		) );

		// Call method, passing in refresh value
		verify( $rr->updateById( 1234, array(), array(), true ) )->true();
		verify( $rr->updateById( 1234, array(), array(), false ) )->true();
	}

	/**
	 * test__UpdateByUuid tests that the updateByUuid method
	 * proxies to the updateById method
	 * 
	 * @covers ::updateByUuid
	 */
	public function test__UpdateByUuid() {
		// Mock model
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"updateById" => function( $id, array $data = array(), array $params = array(), $refresh = true ) {
				// Verify this method was called
				verify( true )->true();

				return "foo";
			},
		) );

		// Verify return value
		verify( $rr->updateByUuid( 1234 ) )->equals( "foo" );
	}

	/**
	 * test__UpdateOne tests that the updateOne method
	 * chooses the right method to use for updating a model
	 * 
	 * @covers ::updateOne
	 */
	public function test__UpdateOne() {
		// Mock model
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"usesUuids" => false,
			"updateById" => function( $id, array $data = array(), array $params = array(), $refresh = true ) {
				// Verify this method was called
				verify( true )->true();

				return $id;
			},
		) );

		// Verify method detects static variable, chooses method, and filters ID if needed
		verify( $rr->updateOne( "1234" ) === 1234 )->true();

		// Mock model
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"usesUuids" => true,
			"updateByUuid" => function( $id, array $data = array(), array $params = array(), $refresh = true ) {
				// Verify this method was called
				verify( true )->true();

				return $id;
			},
		) );

		// Verify method detects static variable, chooses method, and filters ID if needed
		verify( $rr->updateOne( "1234" ) === "1234" )->true();
	}

	/**
	 * test__DeleteByIdChecksClassAttributes tests that the deleteById method
	 * checks various class attributes before attempting to delete the model
	 * 
	 * @covers ::deleteById
	 */
	public function test__DeleteByIdChecksClassAttributes() {
		// Verify an empty input returns false
		verify( $this->_rr->deleteById( 0 ) )->false();

		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"beforeDelete" => function() {
				return false;
			}
		) );

		// Verify method checks beforeDelete
		verify( $rr->deleteById( 1234 ) )->false();
	}

	/**
	 * test__DeleteByIdChecksForErrors tests that the deleteById method
	 * checks if a model has errors after delete
	 * 
	 * @covers ::deleteById
	 */
	public function test__DeleteByIdChecksForErrors() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"crud-behavior" => Stub::make( "RestfulRecord\behaviors\CrudBehavior", array(
						"delete" => function( $route = "", array $params = array() ) {
							return "foo";
						}
					) ),
					"errors-behavior" => Stub::make( "RestfulRecord\behaviors\ErrorsBehavior", array(
						"hasErrors" => function() {
							return true;
						}
					) ),
					"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
				);
			},
			"beforeDelete" => function() {
				return true;
			},
		) );

		// Verify models with errors return false
		verify( $rr->deleteById( 1234 ) )->false();
	}

	/**
	 * test__DeleteById tests that the deleteById method
	 * attempts to delete a model
	 * 
	 * @covers ::deleteById
	 */
	public function test__DeleteById() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"_attributes" => array( "foo" => "bar", ),
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"crud-behavior" => Stub::make( "RestfulRecord\behaviors\CrudBehavior", array(
						"delete" => function( $route = "", array $params = array() ) {
							return "foo";
						}
					) ),
					"errors-behavior" => Stub::make( "RestfulRecord\behaviors\ErrorsBehavior", array(
						"hasErrors" => function() {
							return false;
						}
					) ),
					"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
				);
			},
			"beforeDelete" => function() {
				return true;
			},
		) );

		// Verify models with errors return false
		verify( $rr->deleteById( 1234 ) )->true();

		// Verify attributes were unset
		verify( $rr->getAttributes() )->equals( array() );
	}

	/**
	 * test__DeleteByUuid tests that the deleteByUuid method
	 * proxies to the deleteById method
	 * 
	 * @covers ::deleteByUuid
	 */
	public function test__DeleteByUuid() {
		// Mock model
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"deleteById" => function( $id, array $params = array() ) {
				// Verify this method was called
				verify( true )->true();

				return "foo";
			},
		) );

		// Verify return value
		verify( $rr->deleteByUuid( 1234 ) )->equals( "foo" );
	}

	/**
	 * test__DeleteOne tests that the deleteOne method
	 * chooses the right method to use for deleting a model
	 * 
	 * @covers ::deleteOne
	 */
	public function test__DeleteOne() {
		// Mock model
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"usesUuids" => false,
			"deleteById" => function( $id, array $data = array(), array $params = array(), $refresh = true ) {
				// Verify this method was called
				verify( true )->true();

				return $id;
			},
		) );

		// Verify method detects static variable, chooses method, and filters ID if needed
		verify( $rr->deleteOne( "1234" ) === 1234 )->true();

		// Mock model
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"usesUuids" => true,
			"deleteByUuid" => function( $id, array $data = array(), array $params = array(), $refresh = true ) {
				// Verify this method was called
				verify( true )->true();

				return $id;
			},
		) );

		// Verify method detects static variable, chooses method, and filters ID if needed
		verify( $rr->deleteOne( "1234" ) === "1234" )->true();
	}

	/**
	 * test__Save tests that the save method
	 * calls either create or update depending on the newness of the model
	 * 
	 * @covers ::save
	 */
	public function test__Save() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"isNewResource" => function() {
				return true;
			},
			"create" => function( array $data = null, $refresh = true ) {
				return "create";
			},
		) );

		// Verify create response is returned
		verify( $rr->save() )->equals( "create" );

		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"isNewResource" => function() {
				return false;
			},
			"update" => function( array $data = null, $refresh = true ) {
				return "update";
			},
		) );

		// Verify update response is returned
		verify( $rr->save() )->equals( "update" );
	}

	/**
	 * test__CreateChecksClassAttributes tests that the create method
	 * checks various class attributes before attempting to create the model
	 * 
	 * @covers ::create
	 */
	public function test__CreateChecksClassAttributes() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"isNewResource" => function() {
				return false;
			},
		) );

		// Verify method checks for new resource status
		verify( $rr->create() )->false();

		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"isNewResource" => function() {
				return true;
			},
			"beforeSave" => function() {
				return false;
			}
		) );

		// Verify method checks beforeSave
		verify( $rr->create() )->false();
	}

	/**
	 * test__CreateChecksCreateResource tests that the create method
	 * checks if a resource was created
	 * 
	 * @covers ::create
	 */
	public function test__CreateChecksCreateResource() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"crud-behavior" => Stub::make( "RestfulRecord\behaviors\CrudBehavior", array(
						"post" => function( $route = "", array $data = null, array $params = array() ) {
							return "foo";
						}
					) ),
					"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
					"resource-behavior" => Stub::make( "RestfulRecord\behaviors\ResourceBehavior", array(
						"createResource" => function( $response = array(), $fromCollection = false ) {
							// Verify post response if passed through
							verify( $response )->equals( "foo" );

							return null;
						}
					) ),
				);
			},
			"getAttributesToSend" => function( $attributes ) {
				return array();
			},
			"isNewResource" => function() {
				return true;
			},
			"beforeSave" => function() {
				return true;
			}
		) );

		// Verify method checks createResource
		verify( $rr->create() )->false();
	}

	/**
	 * test__Create tests that the create method
	 * attempts to create a model
	 * 
	 * @covers ::create
	 */
	public function test__Create() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"behaviors" => function() {
				return array(
					"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
					"crud-behavior" => Stub::make( "RestfulRecord\behaviors\CrudBehavior", array(
						"post" => function( $route = "", array $data = null, array $params = array() ) {
							return "foo";
						}
					) ),
					"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
					"resource-behavior" => Stub::make( "RestfulRecord\behaviors\ResourceBehavior", array(
						"createResource" => function( $response = array(), $fromCollection = false ) {
							return Stub::make( "RestfulRecord\RestfulRecord", array(
								"getId" => function() {
									return 1234;
								}
							) );
						}
					) ),
				);
			},
			"getAttributesToSend" => function( $attributes ) {
				return array();
			},
			"isNewResource" => function() {
				return true;
			},
			"beforeSave" => function() {
				return true;
			},
			"refresh" => function() {
				return false;
			}
		) );

		// Verify method returns true on model creation
		verify( $rr->create() )->true();
	}

	/**
	 * test__UpdateChecksClassAttributes tests that the update method
	 * checks various class attributes before attempting to update the model
	 * 
	 * @covers ::update
	 */
	public function test__UpdateChecksClassAttributes() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"isNewResource" => function() {
				return true;
			},
		) );

		// Verify method checks for new resource status
		verify( $rr->update() )->false();

		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"isNewResource" => function() {
				return false;
			},
			"beforeSave" => function() {
				return false;
			}
		) );

		// Verify method checks beforeSave
		verify( $rr->update() )->false();
	}

	/**
	 * test__Update tests that the update method
	 * returns the status of a update by ID operation
	 * 
	 * @covers ::update
	 */
	public function test__Update() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"isNewResource" => function() {
				return false;
			},
			"beforeSave" => function() {
				return true;
			},
			"getAttributesToSend" => function( $attributes ) {
				return array();
			},
			"updateOne" => function( $id, array $data = array(), array $params = array(), $refresh = true ) {
				return "foo";
			}
		) );

		// Verify method returns update status
		verify( $rr->update() )->equals( "foo" );
	}

	/**
	 * test__DestroyChecksClassAttributes tests that the destroy method
	 * checks various class attributes before attempting to destroy the model
	 * 
	 * @covers ::destroy
	 */
	public function test__DestroyChecksClassAttributes() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"isNewResource" => function() {
				return true;
			},
		) );

		// Verify method checks for new resource status
		verify( $rr->destroy() )->false();

		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"isNewResource" => function() {
				return false;
			},
			"beforeDelete" => function() {
				return false;
			}
		) );

		// Verify method checks beforeDelete
		verify( $rr->destroy() )->false();
	}

	/**
	 * test__Destroy tests that the destroy method
	 * returns the status of a delete by ID operation
	 * 
	 * @covers ::destroy
	 */
	public function test__Destroy() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"isNewResource" => function() {
				return false;
			},
			"beforeDelete" => function() {
				return true;
			},
			"deleteOne" => function( $id, array $params = array() ) {
				return "foo";
			}
		) );

		// Verify method returns delete status
		verify( $rr->destroy() )->equals( "foo" );
	}

	/**
	 * test__RefreshChecksClassAttributes tests that the refresh method
	 * checks various class attributes before attempting to refresh
	 * 
	 * @covers ::refresh
	 */
	public function test__RefreshChecksClassAttributes() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"isNewResource" => function() {
				return true;
			},
		) );

		// Verify method checks for new resource status
		verify( $rr->refresh() )->false();

		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"isNewResource" => function() {
				return false;
			},
			"findOne" => function( $id, array $params = array() ) {
				return null;
			}
		) );

		// Verify method checks for find by ID success
		verify( $rr->refresh() )->false();
	}

	/**
	 * test__Refresh tests that the refresh method
	 * reset's a model's attributes
	 * 
	 * @covers ::refresh
	 */
	public function test__Refresh() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"isNewResource" => function() {
				return false;
			},
			"findOne" => function( $id, array $params = array() ) {
				return Stub::make( "RestfulRecord\RestfulRecord", array(
					"_attributes" => array( "foo" => "bar", ),
				) );
			}
		) );

		// Verify method attributes are empty
		verify( $rr->getAttributes() )->equals( array() );

		// Verify method returns true after success
		verify( $rr->refresh() )->true();

		// Verify method attributes were reset
		verify( $rr->getAttributes() )->equals( array( "foo" => "bar", ) );
	}

	/**
	 * test__CacheKeys tests that the cacheKeys method
	 * properly returns cache key templates
	 * 
	 * @covers ::cacheKeys
	 */
	public function test__CacheKeys() {
		// Get cache keys
		$cacheKeys = $this->_rr->cacheKeys();

		// Verify cache keys
		verify( $cacheKeys[ "resource" ] )->equals( "{endpoint}:{id}" );
		verify( $cacheKeys[ "collection" ] )->equals( "{endpoint}" );
	}

	/**
	 * test__GetCacheKeyMacros tests that the getcacheKeyMacros method
	 * returns any array of macros
	 * 
	 * @covers ::getcacheKeyMacros
	 */
	public function test__GetCacheKeyMacros() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"getId" => function() {
				return 1234;
			},
			"restConfig" => function() {
				return array(
					"endpoint" => "foo",
				);
			},
		) );

		// Verify macros array is returned
		verify( $rr->getcacheKeyMacros() )->equals( array(
			"{id}" => 1234,
			"{endpoint}" => "foo",
		) );
	}

	/**
	 * test__GetUrlMacros tests that the getUrlMacros method
	 * returns any array of macros
	 * 
	 * @covers ::getUrlMacros
	 */
	public function test__GetUrlMacros() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"getId" => function() {
				return 1234;
			},
			"restConfig" => function() {
				return array(
					"endpoint" => "foo",
					"version" => "v1",
				);
			},
		) );

		// Verify macros array is returned
		verify( $rr->getUrlMacros() )->equals( array(
			":id" => 1234,
			":endpoint" => "foo",
			":version" => "v1",
		) );
	}

	/**
	 * test__RestConfig tests that the restConfig method
	 * return's a model's component's attributes
	 * 
	 * @covers ::restConfig
	 */
	public function test__RestConfig() {
		// Mock RestfulRecord
		$rr = Stub::make( "RestfulRecord\RestfulRecord", array(
			"getComponent" => function() {
				return Stub::make( "RestfulRecord\RestfulRecordComponent", array(
					"attributes" => array( "foo" => "bar" ),
				) );
			}
		) );

		// Verify component attributes are returned
		verify( $rr->restConfig() )->equals( array( "foo" => "bar", ) );
	}

	/**
	 * test__Routes tests that the routes method
	 * properly returns routes
	 * 
	 * @covers ::routes
	 */
	public function test__Routes() {
		// Get routes
		$routes = $this->_rr->routes();

		// Verify routes
		verify( $routes[ "resource" ] )->equals( "/:version/:endpoint/:id" );
		verify( $routes[ "collection" ] )->equals( "/:version/:endpoint" );
	}
}
