<?php

namespace src\RestfulRecord\behaviors;

use \Codeception\Util\Stub;
use GuzzleHttp\Psr7\Response;
use RestfulRecord\RestfulRecord;
use RestfulRecord\RestfulRecordCache;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use Yii;

/**
 * @coversDefaultClass RestfulRecord\behaviors\CacheBehavior
 */
class CacheBehaviorTest extends \Codeception\TestCase\Test {
	
	// An instance of the behavior to be tested
	private $_behavior;

	// An instance of RestfulRecord to be used as the behavior's owner
	private $_rr;

	// Codeception tester
	protected $tester;

	protected function _before() {
		// Set up RestfulRecord
		$this->_rr = new RestfulRecord();

		// Set up behavior
		$this->_behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior" );

		// Set behavior owner
		$this->_behavior->owner = $this->_rr;
	}

	/**
	 * test__Init tests that the init method
	 * checks if the application has a cache component and sets it if needed
	 * 
	 * @covers ::init
	 */
	public function test__Init() {
		// Set component
		Yii::$app->set( $this->_behavior->cacheComponentId, Stub::make( "RestfulRecord\RestfulRecordCache" ) );

		// Verify previous cache component value
		verify( $this->_behavior->getCacheComponent() )->null();

		// Call method
		$this->_behavior->init();

		// Verify cache component after method call
		verify( $this->_behavior->getCacheComponent() )->notNull();
		verify( $this->_behavior->getCacheComponent() instanceOf RestfulRecordCache )->true();
	}

	/**
	 * test__AddKeyToMasterKeyList tests that the addKeyToMasterKeyList method
	 * calls an internal method for non-empty keys and returns it's result
	 * 
	 * @covers ::addKeyToMasterKeyList
	 */
	public function test__AddKeyToMasterKeyList() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"handleResourceMasterKeyUpdate" => function( array $data = array() ) {
				return $data;
			},
		) );

		// Call method with invalid key
		verify( $behavior->addKeyToMasterKeyList( "" ) )->false();

		// Call method with valid key
		$result = $behavior->addKeyToMasterKeyList( "foo" );

		// Verify method return result
		verify( $result )->equals( array( "key" => "foo", ) );
	}

	/**
	 * test__CacheHandlesArguments tests that the cache method
	 * handles different argument types and sets internal states based on them
	 * 
	 * @covers ::cache
	 */
	public function test__CacheHandlesArguments() {
		// Store default cache configuration
		$default = $this->tester->invokeMethod( $this->_behavior, "getDefaultCacheConfig" );

		// Verify strings don't set cache configs
		verify( $this->_behavior->cache( "foo" ) )->equals( $this->_rr );
		verify( $this->_behavior->getCacheConfig() )->equals( array() );

		// Verify booleans set/don't set cache configs
		verify( $this->_behavior->cache( false ) )->equals( $this->_rr );
		verify( $this->_behavior->getCacheConfig() )->equals( array() );

		verify( $this->_behavior->cache( true ) )->equals( $this->_rr );
		verify( $this->_behavior->getCacheConfig() )->equals( $default );

		// Verify arrays override/merge with default cache configs
		verify( $this->_behavior->cache( array( "foo" => "bar", ) ) )->equals( $this->_rr );
		verify( $this->_behavior->getCacheConfig() )->equals( ArrayHelper::merge( $default, array( "foo" => "bar", ) ) );

		// Verify old syntax doesn't break method
		verify( $this->_behavior->cache( "foo", 300 ) )->equals( $this->_rr );
		verify( $this->_behavior->getCacheConfig() )->equals( array() );
	}

	/**
	 * test__ConfigureCacheChecksConfig tests that the configureCache method
	 * checks for an empty cache config, indicating caching is disabled for the current request
	 * 
	 * @covers ::configureCache
	 */
	public function test__ConfigureCacheChecksConfig() {
		// Verify owner is returned
		verify( $this->_behavior->configureCache() )->equals( $this->_rr );
	}

	/**
	 * test__ConfigureCache tests that the configureCache method
	 * generates a cache key, resets cache type, and stores request options
	 * 
	 * @covers ::configureCache
	 */
	public function test__ConfigureCache() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"owner" => $this->_rr,
			"_cacheConfig" => array(
				"foo" => "bar",
			),
			"generateCacheKey" => function() {
				return "key";
			},
			"formActualType" => function() {
				return "type";
			}
		) );

		// Verify return value
		verify( $behavior->configureCache( array( "foo" => "bar", ) ) )->equals( $this->_rr );

		// Store cache config
		$config = $behavior->getCacheConfig();

		// Verify key was set
		verify( $config[ "key" ] )->equals( "key" );

		// Verify type was set
		verify( $config[ "type" ] )->equals( "type" );

		// Verify options were set
		verify( $behavior->getRequestOptions() )->equals( array( "foo" => "bar", ) );
	}

	/**
	 * test__GetResponseFromCacheChecksCacheComponent tests that the getResponseFromCache method
	 * returns false when no cache component is found
	 * 
	 * @covers ::getResponseFromCache
	 */
	public function test__GetResponseFromCacheChecksCacheComponent() {
		// Explicitely set cache component
		$this->tester->invokeMethod( $this->_behavior, "setCacheComponent", array( null ) );

		// Verify return value without cache component
		verify( $this->_behavior->getResponseFromCache() )->false();
	}

	/**
	 * test__GetResponseFromCacheChecksCache tests that the getResponseFromCache method
	 * returns false when the cache doesn't contain a key
	 * 
	 * @covers ::getResponseFromCache
	 */
	public function test__GetResponseFromCacheChecksCache() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"getCacheConfig" => function() {
				return array(
					"key" => "foo",
				);
			},
			"getCacheComponent" => function() {
				return Stub::make( "RestfulRecord\RestfulRecordCache", array(
					"exists" => function( $key ) {
						// Verify key
						verify( $key )->equals( "foo" );

						return false;
					}
				) );
			}
		) );

		// Verify return value
		verify( $behavior->getResponseFromCache() )->false();
	}

	/**
	 * test__GetResponseFromCache tests that the getResponseFromCache method
	 * returns a value from cache when found
	 * 
	 * @covers ::getResponseFromCache
	 */
	public function test__GetResponseFromCache() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"getCacheComponent" => function() {
				return Stub::make( "RestfulRecord\RestfulRecordCache", array(
					"exists" => function() {
						return true;
					},
					"hget" => function() {
						return array( "foo" => "bar", );
					}
				) );
			},
			"setWasFoundInCache" => function() {
				// Verify this method was called
				verify( true )->true();
			}
		) );

		// Verify return value
		verify( $behavior->getResponseFromCache() )->equals( array( "foo" => "bar", ) );
	}

	public function test__SetResponseInCacheChecksIfShouldCache() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"shouldUseCache" => function() {
				return false;
			},
			"resetState" => function() {} // Noop
		) );

		// Verify output
		verify( $behavior->setResponseInCache() )->false();

		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"shouldUseCache" => function() {
				return true;
			},
			"wasFoundInCache" => function() {
				return true;
			},
			"resetState" => function() {} // Noop
		) );

		// Verify output
		verify( $behavior->setResponseInCache() )->false();
	}
	
	/**
	 * test__SetResponseInCacheChecksInput tests that the setResponseInCache method
	 * checks input and cache config settings before storing items in cache
	 * 
	 * @covers ::setResponseInCache
	 * @dataProvider provider_SetResponseInCacheInput
	 */
	public function test__SetResponseInCacheChecksInput( $behavior, $input = array(), $result = false ) {
		// Verify method checks input
		verify( $behavior->setResponseInCache( $input ) )->equals( $result );
	}

	/**
	 * test__SetResponseInCache tests that the setResponseInCache method
	 * forms cache data and returns the result of setting it in cache
	 * 
	 * @covers ::setResponseInCache
	 */
	public function test__SetResponseInCache() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"owner" => Stub::make( "RestfulRecord\RestfulRecord", array(
				"behaviors" => function() {
					return array(
						"api-responses-behavior" => Stub::make( "RestfulRecord\behaviors\ApiResponsesBehavior", array(
							"getApiResponse" => function() {
								return new Response( 200, array(), Json::encode( array(
									"foo" => "bar",
									"test" => 1234,
								) ) );
							}
						) ),
						"cache-behavior" => "RestfulRecord\behaviors\CacheBehavior",
						"crud-behavior" => "RestfulRecord\behaviors\CrudBehavior",
						"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
						"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
					);
				}
			) ),
			"shouldUseCache" => function() { return true; },
			"wasFoundInCache" => function() { return false; },
			"getCacheComponent" => function() {
				return Stub::make( "RestfulRecord\RestfulRecordCache", array(
					"hmset" => function( $key, $fields, $duration ) {
						// Verify input
						verify( $key )->equals( "foo" );
						verify( $fields )->equals( array( "foo", "resource" ) );

						return "foo";
					}
				) );
			},
			"getCacheConfig" => function() {
				return array(
					"type" => "resource",
					"key" => "foo",
				);
			},
			"formResourceStore" => function() {
				// Verify this method was called
				verify( true )->true();

				return array( "foo" => "resource" );
			},
			"resetState" => function() {
				// Verify this method was called
				verify( true )->true();
			},
			"updateMasterKeyLists" => function() {
				// Verify this method was called
				verify( true )->true();
			}
		) );

		// Verify method returns cache result
		verify( $behavior->setResponseInCache() )->equals( "foo" );
	}

	/**
	 * test__ShouldUseCache tests that the shouldUseCache method
	 * returns a boolean variable based on if cache should be used
	 * 
	 * @covers ::shouldUseCache
	 */
	public function test__ShouldUseCache() {
		// Verify false is always returned for now
		verify( $this->_behavior->shouldUseCache() )->false();
	}

	/**
	 * test__GettersAndSetters tests various getters and setters
	 * 
	 * @covers ::getCacheComponent
	 * @covers ::setCacheComponent
	 * @covers ::getCacheConfig
	 * @covers ::setCacheConfig
	 * @covers ::getPreviousCacheConfig
	 * @covers ::setPreviousCacheConfig
	 * @covers ::getRequestOptions
	 * @covers ::setRequestOptions
	 * @covers ::wasFoundInCache
	 * @covers ::setWasFoundInCache
	 */
	public function test__GettersAndSetters() {
		$getters = array(
			"getCacheComponent" => array(
				"default" => 1234,
				"setter" => "setCacheComponent",
			),
			"getCacheConfig" => array(
				"default" => array( "foo" => "bar", ),
				"setter" => "setCacheConfig",
			),
			"getPreviousCacheConfig" => array(
				"default" => array( "foo" => "bar", ),
				"setter" => "setPreviousCacheConfig",
			),
			"getRequestOptions" => array(
				"default" => array( "test" => "one", ),
				"setter" => "setRequestOptions",
			),
			"wasFoundInCache" => array(
				"default" => false,
				"setter" => "setWasFoundInCache",
			),
		);

		foreach ( $getters as $getter => $arr ) {
			// Verify return value from setter
			verify( $this->tester->invokeMethod( $this->_behavior, $arr[ "setter" ], array( $arr[ "default" ] ) ) )->equals( $this->_behavior );
			
			// Verify previous class object
			verify( $this->_behavior->{ $getter }() )->equals( $arr[ "default" ] );
		}
	}

	/**
	 * test__FilterKeyArrayPart tests that the filterKeyArrayPart method
	 * filters out blacklisted data
	 * 
	 * @covers ::filterKeyArrayPart
	 */
	public function test__FilterKeyArrayPart() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"owner" => Stub::make( "RestfulRecord\RestfulRecord", array(
				"restConfig" => function() {
					return array(
						"foo" => array( "blacklist", ),
					);
				},
			) ),
		) );

		// Set input
		$input = array( "foo" => "bar", "blacklist" => "baz", );

		// Set expected output
		$output = array( "foo" => "bar", );

		// Verify return value
		verify( $this->tester->invokeMethod( $behavior, "filterKeyArrayPart", array( $input, "foo" ) ) )->equals( $output );
	}

	/**
	 * test__FlattenArray tests that the flattenArray method
	 * takes an associative array and flattens it
	 * 
	 * @covers ::flattenArray
	 * @dataProvider provider_FlattenArray
	 */
	public function test__FlattenArray( $input, $output ) {
		// Verify method checks for invalid values
		verify( $this->tester->invokeMethod( $this->_behavior, "flattenArray", array( $input ) ) )->equals( $output );
	}

	/**
	 * test__FormActualType tests that the formActualType method
	 * properly sets up a type and checks for it's validity
	 * 
	 * @covers ::formActualType
	 */
	public function test__FormActualType() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"owner" => Stub::make( "RestfulRecord\RestfulRecord", array(
				"cacheKeys" => function() {
					return array(
						"foo" => "bar",
					);
				}
			) ),
			"getTypeFromRequestOptions" => function() {
				// Verify this method was called
				verify( true )->true();

				return "bar";
			}
		) );

		// Verify auto value calls request options method
		verify( $this->tester->invokeMethod( $behavior, "formActualType" ) )->false();

		// Set cache config
		$this->tester->invokeMethod( $behavior, "setCacheConfig", array( array(
			"type" => "foo",
		) ) );

		// Verify non-auto value only checks cacheKeys keys
		verify( $this->tester->invokeMethod( $behavior, "formActualType" ) )->equals( "foo" );
	}

	/**
	 * test__FormAwareKeyPart tests that the formAwareKeyPart method
	 * forms a key part string and returns it
	 * 
	 * @covers ::formAwareKeyPart
	 */
	public function test__FormAwareKeyPart() {
		// Explicitely set delimiter
		$this->_behavior->cacheKeyDelimiter = ":";

		// Verify empty data returns an empty string
		verify( $this->tester->invokeMethod( $this->_behavior, "formAwareKeyPart" ) )->equals( "" );

		// Verify return value
		verify( $this->tester->invokeMethod( $this->_behavior, "formAwareKeyPart", array(
			"foo",
			array( "test" => "one" ),
		) ) )->equals( ":foo:".Json::encode( array( "test" => "one", ) ) );
	}

	/**
	 * test__FormCollectionResourceKeys tests that the formCollectionResourceKeys method
	 * returns an array of resource cache keys generated from a collection
	 * 
	 * @covers ::formCollectionResourceKeys
	 */
	public function test__FormCollectionResourceKeys() {
		// Form collection to send
		$collection = array(
			Stub::make( "RestfulRecord\RestfulRecord", array(
				"behaviors" => function() {
					return array(
						"cache-behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
							"getCacheConfig" => function() {
								return array( "key" => "foo", );
							}
						) ),
					);
				}
			) ),
			"test",
			Stub::make( "RestfulRecord\RestfulRecord", array(
				"behaviors" => function() {
					return array(
						"cache-behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
							"getCacheConfig" => function() {
								return array();
							}
						) ),
					);
				}
			) ),
			Stub::make( "RestfulRecord\RestfulRecord", array(
				"behaviors" => function() {
					return array(
						"cache-behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
							"getCacheConfig" => function() {
								return array( "key" => "bar", );
							}
						) ),
					);
				}
			) ),
		);

		// Verify return value
		verify( $this->tester->invokeMethod( $this->_behavior, "formCollectionResourceKeys", array( $collection ) ) )->equals( array(
			"foo",
			"bar",
		) );
	}

	/**
	 * test__FormCollectionStore tests that the formCollectionStore method
	 * returns an array of collection store data
	 * 
	 * @covers ::formCollectionStore
	 */
	public function test__FormCollectionStore() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"formCollectionResourceKeys" => function() {
				return array( "foo", "bar", );
			}
		) );

		// Call method, store response
		$response = $this->tester->invokeMethod( $behavior, "formCollectionStore" );

		// Verify store keys and values
		verify( array_keys( $response ) )->contains( "type" );
		verify( array_keys( $response ) )->contains( "keys" );
		verify( array_keys( $response ) )->contains( "data" );

		verify( $response[ "type" ] )->equals( "collection" );
		verify( $response[ "keys" ] )->equals( Json::encode( array( "foo", "bar" ) ) );
		verify( $response[ "data" ] )->equals( "" );
	}

	/**
	 * test__FormResourceStore tests that the formResourceStore method
	 * returns an array of resource store data
	 * 
	 * @covers ::formResourceStore
	 */
	public function test__FormResourceStore() {
		// Call method, store response
		$response = $this->tester->invokeMethod( $this->_behavior, "formResourceStore" );

		// Verify store keys and values
		verify( array_keys( $response ) )->contains( "type" );
		verify( array_keys( $response ) )->contains( "data" );

		verify( $response[ "type" ] )->equals( "resource" );
		verify( $response[ "data" ] )->equals( "" );
	}

	/**
	 * test__GenerateCacheKeyUsesCustomKey tests that the generateCacheKey method
	 * uses a custom key if one is found
	 * 
	 * @covers ::generateCacheKey
	 */
	public function test__GenerateCacheKeyUsesCustomKey() {
		// Set cache config
		$this->tester->invokeMethod( $this->_behavior, "setCacheConfig", array( array(
			"key" => "foo",
		) ) );

		// Verify custom key is returned
		verify( $this->tester->invokeMethod( $this->_behavior, "generateCacheKey" ) )->equals( "foo" );
	}

	/**
	 * test__GenerateCacheKeyChecksType tests that the generateCacheKey method
	 * checks for a valid type
	 * 
	 * @covers ::generateCacheKey
	 */
	public function test__GenerateCacheKeyChecksType() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"formActualType" => function() {
				return false;
			}
		) );

		// Verify type is checked
		verify( $this->tester->invokeMethod( $this->_behavior, "generateCacheKey" ) )->false();
	}

	/**
	 * test__GenerateCacheKey tests that the generateCacheKey method
	 * returns a formed cache key
	 * 
	 * @covers ::generateCacheKey
	 */
	public function test__GenerateCacheKey() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"_cacheConfig" => array(
				"paramAware" => false,
				"headerAware" => false,
			),
			"owner" => Stub::make( "RestfulRecord\RestfulRecord", array(
				"cacheKeys" => function() {
					return array(
						"resource" => "foo",
						"collection" => "bar",
					);
				},
				"getUtils" => function() {
					return Stub::make( "RestfulRecord\RestfulRecordUtils", array(
						"replaceMacros" => function() {
							return "key";
						}
					) );
				}
			) ),
			"formActualType" => function() {
				return "resource";
			},
			"formAwareKeyPart" => function( $type = "" ) {
				return ":".$type;
			},
			"getRequestOptions" => function() {
				return array(
					"params" => array(
						"foo" => "bar",
					),
					"headers" => array(
						"test" => "one",
					),
				);
			}
		) );

		// Verify return value
		verify( $this->tester->invokeMethod( $behavior, "generateCacheKey" ) )->equals( "key" );

		// Set param aware
		$this->tester->invokeMethod( $behavior, "setCacheConfig", array( array(
			"paramAware" => true,
			"headerAware" => false,
		) ) );

		// Verify return value
		verify( $this->tester->invokeMethod( $behavior, "generateCacheKey" ) )->equals( "key:params" );

		// Set param aware
		$this->tester->invokeMethod( $behavior, "setCacheConfig", array( array(
			"paramAware" => false,
			"headerAware" => true,
		) ) );

		// Verify return value
		verify( $this->tester->invokeMethod( $behavior, "generateCacheKey" ) )->equals( "key:headers" );
	}

	/**
	 * test__GenerateCacheKeySupportsBlacklistParams tests that the generateCacheKey method
	 * returns a formed cache key, excluding blacklisted data
	 * 
	 * @covers ::generateCacheKey
	 */
	public function test__GenerateCacheKeySupportsBlacklistData() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"_cacheConfig" => array(
				"paramAware" => false,
				"headerAware" => false,
			),
			"owner" => Stub::make( "RestfulRecord\RestfulRecord", array(
				"cacheKeys" => function() {
					return array(
						"resource" => "foo",
						"collection" => "bar",
					);
				},
				"getUtils" => function() {
					return Stub::make( "RestfulRecord\RestfulRecordUtils", array(
						"replaceMacros" => function() {
							return "key";
						}
					) );
				},
				"restConfig" => function() {
					return array(
						"blacklistedCacheParams" => array( "blacklist", ),
						"blacklistedCacheHeaders" => array( "blacklist", ),
					);
				},
			) ),
			"formActualType" => function() {
				return "resource";
			},
			"getRequestOptions" => function() {
				return array(
					"params" => array(
						"foo" => "bar",
						"blacklist" => "foo",
					),
					"headers" => array(
						"test" => "one",
						"blacklist" => "foo",
					),
				);
			}
		) );

		// Set param/header aware
		$this->tester->invokeMethod( $behavior, "setCacheConfig", array( array(
			"paramAware" => true,
			"headerAware" => true,
		) ) );

		// Verify return value
		verify( $this->tester->invokeMethod( $behavior, "generateCacheKey" ) )
			->equals( "key:params:{\"foo\":\"bar\"}:headers:{\"test\":\"one\"}" );
	}

	/**
	 * test__GetCollectionResourceIds tests that the getCollectionResourceIds method
	 * returns an array of collection resource IDs
	 * 
	 * @covers ::getCollectionResourceIds
	 */
	public function test__GetCollectionResourceIds() {
		// Form collection to send
		$collection = array(
			Stub::make( "RestfulRecord\RestfulRecord", array(
				"id" => 1234,
			) ),
			"test",
			Stub::make( "RestfulRecord\RestfulRecord", array(
				"id" => 2345,
			) ),
		);

		// Verify return value
		verify( $this->tester->invokeMethod( $this->_behavior, "getCollectionResourceIds", array( $collection ) ) )->equals( array(
			1234,
			2345,
		) );
	}

	/**
	 * test__GetCollectionTemplate tests that the getCollectionTemplate method
	 * returns a template array of default collection hash data
	 * 
	 * @covers ::getCollectionTemplate
	 */
	public function test__GetCollectionTemplate() {
		// Store return value
		$result = $this->tester->invokeMethod( $this->_behavior, "getCollectionTemplate" );

		// Verify result keys
		verify( array_keys( $result ) )->contains( "type" );
		verify( array_keys( $result ) )->contains( "keys" );
		verify( array_keys( $result ) )->contains( "data" );

		// Verify type
		verify( $result[ "type" ] )->equals( "collection" );
	}

	/**
	 * test__GetDefaultCacheConfig tests that the getDefaultCacheConfig method
	 * returns a template array of default collection hash data
	 * 
	 * @covers ::getDefaultCacheConfig
	 */
	public function test__GetDefaultCacheConfig() {
		// Reset behavior owner
		$this->_behavior->owner = Stub::make( "RestfulRecord\RestfulRecord", array(
			"restConfig" => function() {
				return array(
					"cacheDuration" => 100,
				);
			}
		) );

		// Store return value
		$result = $this->tester->invokeMethod( $this->_behavior, "getDefaultCacheConfig" );

		// Verify result keys
		verify( array_keys( $result ) )->contains( "type" );
		verify( array_keys( $result ) )->contains( "key" );
		verify( array_keys( $result ) )->contains( "duration" );
		verify( array_keys( $result ) )->contains( "paramAware" );
		verify( array_keys( $result ) )->contains( "headerAware" );
		verify( array_keys( $result ) )->contains( "globalKey" );
		verify( array_keys( $result ) )->contains( "cacheEmptyResponses" );

		// Verify type
		verify( $result[ "type" ] )->equals( "auto" );

		// Verify duration
		verify( $result[ "duration" ] )->equals( 100 );
	}

	/**
	 * test__GetMasterKeyClassName tests that the getMasterKeyClassName method
	 * returns a formatted class name
	 * 
	 * @covers ::getMasterKeyClassName
	 */
	public function test__GetMasterKeyClassName() {
		// Verify output
		verify( $this->tester->invokeMethod( $this->_behavior, "getMasterKeyClassName" ) )->equals( "RestfulRecord:RestfulRecord" );
	}

	/**
	 * test__GetResourceTemplate tests that the getResourceTemplate method
	 * returns a template array of default collection hash data
	 * 
	 * @covers ::getResourceTemplate
	 */
	public function test__GetResourceTemplate() {
		// Store return value
		$result = $this->tester->invokeMethod( $this->_behavior, "getResourceTemplate" );

		// Verify result keys
		verify( array_keys( $result ) )->contains( "type" );
		verify( array_keys( $result ) )->contains( "data" );

		// Verify type
		verify( $result[ "type" ] )->equals( "resource" );
	}

	/**
	 * test__GetTypeFromRequestOptions tests that the getTypeFromRequestOptions method
	 * returns a route type value if found, otherwise returns false
	 * 
	 * @covers ::getTypeFromRequestOptions
	 */
	public function test__GetTypeFromRequestOptions() {
		// Verify default value is returned when a key isn't present
		verify( $this->tester->invokeMethod( $this->_behavior, "getTypeFromRequestOptions" ) )->false();

		// Set request options
		$this->tester->invokeMethod( $this->_behavior, "setRequestOptions", array( array(
			"routeType" => "foo",
		) ) );

		// Verify return value when a key is present
		verify( $this->tester->invokeMethod( $this->_behavior, "getTypeFromRequestOptions" ) )->equals( "foo" );
	}

	/**
	 * test__ResetState tests that the resetState method
	 * properly resets instate states on the behavior
	 * 
	 * @covers ::resetState
	 */
	public function test__ResetState() {
		// Set up dummy states
		$this->tester->invokeMethod( $this->_behavior, "setPreviousCacheConfig", array( array( "foo" ) ) );
		$this->tester->invokeMethod( $this->_behavior, "setCacheConfig", array( array( "bar" ) ) );
		$this->tester->invokeMethod( $this->_behavior, "setRequestOptions", array( array( "baz" ) ) );
		$this->tester->invokeMethod( $this->_behavior, "setWasFoundInCache", array( "test" ) );

		// Verify state
		verify( $this->_behavior->getPreviousCacheConfig() )->equals( array( "foo" ) );
		verify( $this->_behavior->getCacheConfig() )->equals( array( "bar" ) );
		verify( $this->_behavior->getRequestOptions() )->equals( array( "baz" ) );
		verify( $this->_behavior->wasFoundInCache() )->equals( "test" );

		// Reset state
		$this->tester->invokeMethod( $this->_behavior, "resetState" );

		// Verify state was reset
		verify( $this->_behavior->getPreviousCacheConfig() )->equals( array( "bar" ) );
		verify( $this->_behavior->getCacheConfig() )->equals( array() );
		verify( $this->_behavior->getCacheConfig() )->equals( array() );
		verify( $this->_behavior->wasFoundInCache() )->false();
	}

	/**
	 * test__UpdateMasterKeyLists tests that the updateMasterKeyLists method
	 * calls various handlers based on input
	 * 
	 * @covers ::updateMasterKeyLists
	 */
	public function test__UpdateMasterKeyLists() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"handleGlobalMasterKeyUpdate" => function() {
				return "foo";
			},
			"handleCollectionMasterKeyUpdate" => function() {
				return "bar";
			},
			"handleResourceMasterKeyUpdate" => function() {
				return "baz";
			},
		) );

		// Verify type is checked
		verify( $this->tester->invokeMethod( $behavior, "updateMasterKeyLists" ) )->false();

		// Verify various return values
		verify( $this->tester->invokeMethod( $behavior, "updateMasterKeyLists", array( array(
			"type" => "foo",
			"globalKey" => true,
		) ) ) )->equals( "foo" );
		verify( $this->tester->invokeMethod( $behavior, "updateMasterKeyLists", array( array(
			"type" => "collection",
		) ) ) )->equals( "bar" );
		verify( $this->tester->invokeMethod( $behavior, "updateMasterKeyLists", array( array(
			"type" => "resource",
		) ) ) )->equals( "baz" );

		// Verify catch-all return value
		verify( $this->tester->invokeMethod( $behavior, "updateMasterKeyLists", array( array(
			"type" => "test",
		) ) ) )->false();
	}

	/**
	 * test__HandleGlobalMasterKeyUpdate tests that the handleGlobalMasterKeyUpdate method
	 * handles setting global master key lists
	 * 
	 * @covers ::handleGlobalMasterKeyUpdate
	 */
	public function test__HandleGlobalMasterKeyUpdate() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"cacheKeyDelimiter" => ":",
			"globalKeyIndicator" => "*",
			"getMasterKeyClassName" => function() {
				return "master-key";
			},
			"rpushToMasterKey" => true,
		) );

		// Verify return value
		verify( $this->tester->invokeMethod( $behavior, "handleGlobalMasterKeyUpdate", array( array( "key" => "foo", ) ) ) )->true();
	}

	/**
	 * test__HandleCollectionMasterKeyUpdate tests that the handleCollectionMasterKeyUpdate method
	 * handles setting collection master key lists
	 * 
	 * @covers ::handleCollectionMasterKeyUpdate
	 */
	public function test__HandleCollectionMasterKeyUpdate() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"cacheKeyDelimiter" => ":",
			"getMasterKeyClassName" => function() {
				return "master-key";
			},
			"getCollectionResourceIds" => function() {
				return array( 1234, );
			},
			"rpushToMasterKey" => true,
		) );

		// Verify return value
		verify( $this->tester->invokeMethod( $behavior, "handleCollectionMasterKeyUpdate", array( array( "key" => "foo", ) ) ) )->true();
	}

	/**
	 * test__HandleResourceMasterKeyUpdate tests that the handleResourceMasterKeyUpdate method
	 * handles setting resource master key lists
	 * 
	 * @covers ::handleResourceMasterKeyUpdate
	 */
	public function test__HandleResourceMasterKeyUpdate() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"cacheKeyDelimiter" => ":",
			"owner" => Stub::make( "RestfulRecord\RestfulRecord", array(
				"id" => 1234,
			) ),
			"getMasterKeyClassName" => function() {
				return "master-key";
			},
			"rpushToMasterKey" => true,
		) );

		// Verify return value
		verify( $this->tester->invokeMethod( $behavior, "handleResourceMasterKeyUpdate", array( array( "key" => "foo", ) ) ) )->true();
	}

	/**
	 * test__RpushToMasterKey tests that the rpushToMasterKey method
	 * checks for a valid cache component and attempts to append a key
	 * to a master key list and set it's TTL
	 * 
	 * @covers ::rpushToMasterKey
	 */
	public function test__RpushToMasterKey() {
		// Verify cache component is needed
		verify( $this->tester->invokeMethod( $this->_behavior, "rpushToMasterKey", array( "", "", ) ) )->false();

		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
			"_masterKeyTTLFactor" => 4,
			"getCacheComponent" => function() {
				return Stub::make( "RestfulRecord\RestfulRecordCache", array(
					"rpush" => function( $key, array $values = array() ) {
						// Verify input
						verify( $key )->equals( "foo" );
						verify( $values )->equals( array( "bar" ) );
					},
					"expire" => function( $key, $expires ) {
						// Verify input
						verify( $key )->equals( "foo" );
						verify( $expires )->equals( 2400 ); // NOTE: Ensures TTL factor was applied
					}
				) );
			},
		) );

		// Verify return value
		verify( $this->tester->invokeMethod( $behavior, "rpushToMasterKey", array( "foo", "bar", 600 ) ) )->true();
	}

	/**
	 * 
	 * Begin Data Providers
	 * 
	 */

	/**
	 * provider_SetResponseInCacheInput provides different sets of data to test various cases within
	 * the setResponseInCache method
	 */
	public function provider_SetResponseInCacheInput() {
		return array(
			array(
				// Response is good, key is missing from config
				"behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
					"owner" => Stub::make( "RestfulRecord\RestfulRecord", array(
						"behaviors" => function() {
							return array(
								"api-responses-behavior" => Stub::make( "RestfulRecord\behaviors\ApiResponsesBehavior", array(
									"getApiResponse" => function() {
										return new Response( 200, array(), Json::encode( array(
											"foo" => "bar",
											"test" => 1234,
										) ) );
									}
								) ),
								"cache-behavior" => "RestfulRecord\behaviors\CacheBehavior",
								"crud-behavior" => "RestfulRecord\behaviors\CrudBehavior",
								"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
								"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
							);
						}
					) ),
					"shouldUseCache" => function() { return true; },
					"wasFoundInCache" => function() { return false; },
					"getCacheComponent" => function() {
						return Stub::make( "RestfulRecord\RestfulRecordCache" );
					},
					"getCacheConfig" => function() {
						return array(
							"type" => "resource",
						);
					}
				) ),
			),
			array(
				// Response is good, type is missing from config
				"behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
					"owner" => Stub::make( "RestfulRecord\RestfulRecord", array(
						"behaviors" => function() {
							return array(
								"api-responses-behavior" => Stub::make( "RestfulRecord\behaviors\ApiResponsesBehavior", array(
									"getApiResponse" => function() {
										return new Response( 200, array(), Json::encode( array(
											"foo" => "bar",
											"test" => 1234,
										) ) );
									}
								) ),
								"cache-behavior" => "RestfulRecord\behaviors\CacheBehavior",
								"crud-behavior" => "RestfulRecord\behaviors\CrudBehavior",
								"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
								"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
							);
						}
					) ),
					"shouldUseCache" => function() { return true; },
					"wasFoundInCache" => function() { return false; },
					"getCacheComponent" => function() {
						return Stub::make( "RestfulRecord\RestfulRecordCache" );
					},
					"getCacheConfig" => function() {
						return array(
							"key" => "bar",
						);
					}
				) ),
			),
			array(
				// Response is bad
				"behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
					"owner" => Stub::make( "RestfulRecord\RestfulRecord", array(
						"behaviors" => function() {
							return array(
								"api-responses-behavior" => Stub::make( "RestfulRecord\behaviors\ApiResponsesBehavior", array(
									"getApiResponse" => function() {
										return new Response( 200, array(), "" );
									}
								) ),
								"cache-behavior" => "RestfulRecord\behaviors\CacheBehavior",
								"crud-behavior" => "RestfulRecord\behaviors\CrudBehavior",
								"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
								"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
							);
						}
					) ),
					"shouldUseCache" => function() { return true; },
					"wasFoundInCache" => function() { return false; },
					"getCacheComponent" => function() {
						return Stub::make( "RestfulRecord\RestfulRecordCache" );
					},
					"getCacheConfig" => function() {
						return array(
							"key" => "bar",
							"type" => "resource",
						);
					}
				) ),
			),
			array(
				// Response is good, type is collection but collection is empty
				"behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
					"owner" => Stub::make( "RestfulRecord\RestfulRecord", array(
						"behaviors" => function() {
							return array(
								"api-responses-behavior" => Stub::make( "RestfulRecord\behaviors\ApiResponsesBehavior", array(
									"getApiResponse" => function() {
										return new Response( 200, array(), Json::encode( array(
											"foo" => "bar",
											"test" => 1234,
										) ) );
									}
								) ),
								"cache-behavior" => "RestfulRecord\behaviors\CacheBehavior",
								"crud-behavior" => "RestfulRecord\behaviors\CrudBehavior",
								"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
								"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
							);
						}
					) ),
					"shouldUseCache" => function() { return true; },
					"wasFoundInCache" => function() { return false; },
					"getCacheComponent" => function() {
						return Stub::make( "RestfulRecord\RestfulRecordCache" );
					},
					"getCacheConfig" => function() {
						return array(
							"key" => "bar",
							"type" => "collection",
						);
					}
				) ),
				"input" => array(
					"collection" => false,
				),
			),
			array(
				// Response is good, collection is empty but cacheEmptyResponses is true
				"behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
					"owner" => Stub::make( "RestfulRecord\RestfulRecord", array(
						"behaviors" => function() {
							return array(
								"api-responses-behavior" => Stub::make( "RestfulRecord\behaviors\ApiResponsesBehavior", array(
									"getApiResponse" => function() {
										return new Response( 200, array(), Json::encode( array(
											"foo" => "bar",
											"test" => 1234,
										) ) );
									}
								) ),
								"cache-behavior" => "RestfulRecord\behaviors\CacheBehavior",
								"crud-behavior" => "RestfulRecord\behaviors\CrudBehavior",
								"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
								"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
							);
						}
					) ),
					"shouldUseCache" => function() { return true; },
					"wasFoundInCache" => function() { return false; },
					"getCacheComponent" => function() {
						return Stub::make( "RestfulRecord\RestfulRecordCache", array(
							"hmset" => function( $key, $fields, $duration ) {
								return true;
							}
						) );
					},
					"getCacheConfig" => function() {
						return array(
							"key" => "bar",
							"type" => "collection",
							"cacheEmptyResponses" => true,
						);
					},
					"formCollectionStore" => function() {
						return array( "foo" => "collection" );
					},
					"resetState" => function() {},
					"updateMasterKeyLists" => function() {},
				) ),
				"input" => array(
					"collection" => false,
				),
				"result" => true,
			),
		);
	}

	/**
	 * provider_FlattenArray provides different sets of data to test various cases within
	 * the flattenArray method
	 */
	public function provider_FlattenArray() {
		return array(
			array(
				"input" => array(
					"foo" => "bar",
					"test" => 1234,
				),
				"output" => array( "foo", "bar", "test", 1234, ),
			),
			array(
				"input" => array(
					"foo" => "bar",
					"test" => array(
						"baz" => 1234,
					),
				),
				"output" => array(),
			)
		);
	}
}
