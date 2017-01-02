<?php

namespace src\RestfulRecord\behaviors;

use Codeception\Util\Stub;
use GuzzleHttp\Message\Response;
use RestfulRecord\behaviors\CrudBehavior;
use RestfulRecord\RestfulRecord;
use RestfulRecord\RestfulRecordRequest;
use yii\helpers\Json;
use Yii;

/**
 * @coversDefaultClass RestfulRecord\behaviors\CrudBehavior
 */
class CrudBehaviorTest extends \Codeception\TestCase\Test {

	// An instance of the behavior to be tested	
	private $_behavior;

	// A mock of RestfulRecord
	private $_mockRestfulRecord;

	// A predicatable set of query inputs
	private $_queryInfo = array(
		"route" => "foo",
		"method" => "PATCH",
		"data" => array(
			"test" => "one",
		),
		"params" => array(
			"foo" => "bar",
		),
	);

	// Codeception tester
	protected $tester;

	protected function _before() {
		// Set up mock
		$this->_mockRestfulRecord = Stub::construct( "RestfulRecord\RestfulRecord", array(), array(
			"restConfig" => function() {
				return array(
					"idProperty" => "id",
					"version" => "foo",
					"endpoint" => "bar",
					"apiUrl" => "http://localhost",
				);
			},
			"routes" => function() {
				return array(
					"foo" => "foo/:endpoint",
					"test" => ":endpoint/:version",
				);
			}
		) );

		// Set up ID
		$this->_mockRestfulRecord->id = "foo";

		// Set up behavior
		$this->_behavior = Stub::construct( "RestfulRecord\behaviors\CrudBehavior", array(), array(
			"query" => function( $info = array() ) {
				return $info;
			},
		) );

		// Set behavior owner
		$this->_behavior->owner = $this->_mockRestfulRecord;
	}

	/**
	 * test__GetAndSetRequest tests that the getRequest and setRequest methods
	 * properly manipulate an internal `_request` property
	 * 
	 * @covers ::getRequest
	 * @covers ::setRequest
	 */
	public function test__GetAndSetRequest() {
		// Verify request was null
		verify( $this->_behavior->getRequest() )->null();

		// Set up new request
		$request = new RestfulRecordRequest( $this->_mockRestfulRecord->getComponent() );

		// Reset request
		$this->tester->invokeMethod( $this->_behavior, "setRequest", array( $request ) );

		// Verify new request
		verify( $this->_behavior->getRequest() )->equals( $request );
	}

	/**
	 * test__Get tests that the get method properly forms a set of request parameters and returns the
	 * results of a query operation
	 * NOTE: query is stubbed to make it predictable
	 * 
	 * @covers ::get
	 * @dataProvider provider_Get
	 */
	public function test__Get( $route, $params, $output ) {
		// Verify data is properly formed
		verify( $this->_behavior->get( $route, $params ) )->equals( $output );
	}

	/**
	 * test__Post tests that the post method properly forms a set of request parameters and returns the
	 * results of a query operation
	 * NOTE: query is stubbed to make it predictable
	 * 
	 * @covers ::post
	 * @dataProvider provider_Post
	 */
	public function test__Post( $route, $data, $params, $output ) {
		// Verify data is properly formed
		verify( $this->_behavior->post( $route, $data, $params ) )->equals( $output );
	}

	/**
	 * test__Put tests that the put method properly forms a set of request parameters and returns the
	 * results of a query operation
	 * NOTE: query is stubbed to make it predictable
	 * 
	 * @covers ::put
	 * @dataProvider provider_Put
	 */
	public function test__Put( $route, $data, $params, $output ) {
		// Verify data is properly formed
		verify( $this->_behavior->put( $route, $data, $params ) )->equals( $output );
	}

	/**
	 * test__Delete tests that the delete method properly forms a set of request parameters and returns the
	 * results of a query operation
	 * NOTE: query is stubbed to make it predictable
	 * 
	 * @covers ::delete
	 * @dataProvider provider_Delete
	 */
	public function test__Delete( $route, $params, $output ) {
		// Verify data is properly formed
		verify( $this->_behavior->delete( $route, $params ) )->equals( $output );
	}

	/**
	 * test__Head tests that the head method properly forms a set of request parameters and returns the
	 * results of a query operation
	 * NOTE: query is stubbed to make it predictable
	 * 
	 * @covers ::head
	 * @dataProvider provider_Head
	 */
	public function test__Head( $route, $params, $output ) {
		// Verify data is properly formed
		verify( $this->_behavior->head( $route, $params ) )->equals( $output );
	}

	/**
	 * test__QueryReturnsNullWithEmptyInput tests that the query method returns
	 * null when invalid input is passed in
	 * 
	 * @covers ::query
	 */
	public function test__QueryReturnsNullWithEmptyInput() {
		// Un-mock query function
		$behavior = new CrudBehavior();

		// Set behavior owner
		$behavior->owner = $this->_mockRestfulRecord;

		// Verify that null is returned when a request info input is empty
		verify( $this->tester->invokeMethod( $behavior, "query" ) )->null();
		verify( $this->tester->invokeMethod( $behavior, "query", array( array() ) ) )->null();
	}

	/**
	 * test__QuerySetsVariousElementsOfARequest tests that the query method
	 * properly sets up a new request and sets it's internal properties as needed
	 * 
	 * @covers ::query
	 * @covers ::getRequest
	 */
	public function test__QuerySetsVariousElementsOfARequest() {
		// Un-mock query function
		$behavior = Stub::construct( "RestfulRecord\behaviors\CrudBehavior", array(), array(
			"owner" => $this->_mockRestfulRecord,
			// Mock request to not actually send cURL
			"getNewRequest" => function() {
				return Stub::construct( "RestfulRecord\RestfulRecordRequest", array( $this->_mockRestfulRecord->getComponent() ), array(
					"query" => function() {
						return array( false, null );
					}
				) );
			},
		) );;

		// Call query method
		$test = $this->tester->invokeMethod( $behavior, "query", array( $this->_queryInfo ) );

		// Cache request
		$request = $behavior->getRequest();

		// Verify a request was created
		verify( $request instanceOf RestfulRecordRequest )->true();

		// Verify various properties were set on the request
		verify( $request->getPath() )->equals( "foo" );
		verify( $request->getMethod() )->equals( "PATCH" );
		verify( $request->getData() )->equals( array( "test" => "one" ) );
		verify( $request->getParams() )->equals( array( "foo" => "bar" ) );
	}

	/**
	 * test__QueryChecksForStatusCode tests that the query method checks a response
	 * for a valid getStatusCode method
	 * 
	 * @covers ::query
	 */
	public function test__QueryChecksForStatusCode() {
		// Un-mock query function
		$behavior = Stub::construct( "RestfulRecord\behaviors\CrudBehavior", array(), array(
			"owner" => $this->_mockRestfulRecord,
			// Mock request to not actually send cURL
			"getNewRequest" => function() {
				return Stub::construct( "RestfulRecord\RestfulRecordRequest", array( $this->_mockRestfulRecord->getComponent() ), array(
					"query" => function() {
						$dummy = new \stdClass();
						$dummy->id = "foo";

						return array( false, $dummy );
					}
				) );
			},
		) );

		// Call query method, check for null return value
		verify( $this->tester->invokeMethod( $behavior, "query", array( $this->_queryInfo ) ) )->null();

		// Verify responses and errors were added
		$error = $behavior->owner->getFirstError( "API" );
		$response = $behavior->owner->getApiResponse();

		verify( $error )->equals( "Invalid API response." );
		verify( $response->id )->equals( "foo" );
	}

	/**
	 * test__QueryExtractsItemsFromValidResponse tests that the query method properly
	 * returns a valid response
	 * 
	 * @covers ::query
	 */
	public function test__QueryExtractsItemsFromValidResponse() {
		// Un-mock query function
		$behavior = Stub::construct( "RestfulRecord\behaviors\CrudBehavior", array(), array(
			"owner" => $this->_mockRestfulRecord,
			"getNewRequest" => function() {
				return Stub::construct( "RestfulRecord\RestfulRecordRequest", array( $this->_mockRestfulRecord->getComponent() ), array(
					"query" => function() {
						$response = Stub::make( "GuzzleHttp\Psr7\Response", array(
							"getBody" => function() {
								return Stub::make( "GuzzleHttp\Psr7\Stream", array(
									"getContents" => function() {
										return Json::encode( array(
											"count" => 0,
											"items" => array(
												array( "id" => 1, ),
												array( "id" => 2, ),
												array( "id" => 3, ),
											),
										) );
									}
								) );
							}
						) );

						return array( false, $response );
					}
				) );
			}
		) );

		// Verify items were returned
		verify( $this->tester->invokeMethod( $behavior, "query", array( $this->_queryInfo ) ) )->equals( array(
			"count" => 0,
			"items" => array(
				array( "id" => 1, ),
				array( "id" => 2, ),
				array( "id" => 3, ),
			),
		) );
	}

	/**
	 * test__QueryExtractsItemsFromValidResponse tests that the query method properly
	 * catches JSON decode errors
	 * 
	 * @covers ::query
	 */
	public function test__QueryCatchesJsonDecodeErrors() {
		// Mock Yii logger
		Yii::setLogger( Stub::make( "yii\log\Logger", array(
			"log" => function( $message, $level, $category = "application" ) {
				// Verify this method was called
				verify( true )->true();
			}
		) ) );

		// Un-mock query function
		$behavior = Stub::construct( "RestfulRecord\behaviors\CrudBehavior", array(), array(
			"owner" => $this->_mockRestfulRecord,
			"getNewRequest" => function() {
				return Stub::construct( "RestfulRecord\RestfulRecordRequest", array( $this->_mockRestfulRecord->getComponent() ), array(
					"query" => function() {
						$response = Stub::make( "GuzzleHttp\Psr7\Response", array(
							"getBody" => function() {
								return Stub::make( "GuzzleHttp\Psr7\Stream", array(
									"getContents" => function() {
										return array();
									}
								) );
							}
						) );

						return array( false, $response );
					}
				) );
			}
		) );

		// Verify items were returned
		verify( $this->tester->invokeMethod( $behavior, "query", array( $this->_queryInfo ) ) )->equals( "" );
	}

	/**
	 * test__QueryExtractsItemsFromValidResponse tests that the query method 
	 * returns null from an invalid response
	 * 
	 * @covers ::query
	 */
	public function test__QueryReturnsNullFromInvalidResponse() {
		// Un-mock query function
		$behavior = Stub::construct( "RestfulRecord\behaviors\CrudBehavior", array(), array(
			"owner" => $this->_mockRestfulRecord,
			// Mock request to not actually send cURL
			"getNewRequest" => function() {
				return Stub::construct( "RestfulRecord\RestfulRecordRequest", array( $this->_mockRestfulRecord->getComponent() ), array(
					"query" => function() {
						$response = Stub::make( "GuzzleHttp\Psr7\Response", array(
							"statusCode" => 404,
						) );

						return array( true, $response );
					}
				) );
			},
		) );

		// Verify null was returned
		verify( $this->tester->invokeMethod( $behavior, "query", array( $this->_queryInfo ) ) )->null();
	}

	/**
	 * test__QueryUsesCache tests that the query method
	 * uses a cached response when it's available
	 * 
	 * @covers ::query
	 */
	public function test__QueryUsesCache() {
		// Mock behavior
		$behavior = Stub::make( "RestfulRecord\behaviors\CrudBehavior", array(
			"owner" => Stub::make( "RestfulRecord\RestfulRecord", array(
				"behaviors" => function() {
					return array(
						"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
						"cache-behavior" => Stub::make( "RestfulRecord\behaviors\CacheBehavior", array(
							"shouldUseCache" => function() {
								return true;
							},
							"getResponseFromCache" => function() {
								return Json::encode( array(
									"count" => 0,
									"items" => array(
										array( "id" => 1, ),
										array( "id" => 2, ),
										array( "id" => 3, ),
									),
								) );
							},
						) ),
						"crud-behavior" => "RestfulRecord\behaviors\CrudBehavior",
						"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
						"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
					);
				}
			) ),
		) );

		// Verify items were returned
		verify( $this->tester->invokeMethod( $behavior, "query", array( $this->_queryInfo ) ) )->equals( array(
			"count" => 0,
			"items" => array(
				array( "id" => 1, ),
				array( "id" => 2, ),
				array( "id" => 3, ),
			),
		) );
	}

	/**
	 * test__EmptyBuildQueryInfoReturnsDefaultOptions tests that the buildQueryInfo method
	 * returns false when no input is present
	 * 
	 * @covers ::buildQueryInfo
	 */
	public function test__EmptyBuildQueryInfoReturnsDefaultOptions() {
		// Verify calling buildQueryInfo without input returns false
		verify( $this->tester->invokeMethod( $this->_behavior, "buildQueryInfo" ) )->false();
	}

	/**
	 * test__BuildQueryInfo tests that the buildQueryInfo method
	 * properly formats query info based on certain input
	 * 
	 * @covers ::buildQueryInfo
	 */
	public function test__BuildQueryInfo() {
		// Form query info
		$queryInfo = $this->tester->invokeMethod( $this->_behavior, "buildQueryInfo", array( array( "route" => "foo" ) ) );

		// Verify calling buildQueryInfo sets up routes
		verify( $queryInfo )->equals( array(
			"routeType" => "",
			"route" => "foo/bar",
			"params" => array(),
			"headers" => array(),
			"method" => "GET",
			"data" => null,
		) );
	}

	/**
	 * test__GetNewRequest tests that the getNewRequest method returns
	 * an new, initialized request object
	 * 
	 * @covers ::getNewRequest
	 */
	public function test__GetNewRequest() {
		// Verify getting a new request returns an instance of RestfulRecordRequest
		verify( $this->tester->invokeMethod( $this->_behavior, "getNewRequest" ) instanceOf RestfulRecordRequest )->true();
	}

	/**
	 * 
	 * Begin Data Providers
	 * 
	 */

	/**
	 * provider_Get provides different sets of data to test various cases within
	 * the get method
	 */
	public function provider_Get() {
		return array(
			array(
				"route" => "foo",
				"params" => array(),
				"output" => array(
					"routeType" => "foo",
					"route" => "foo/bar",
					"method" => "GET",
					"params" => array(),
					"headers" => array(),
					"data" => null,
				),
			),
			array(
				"route" => "bar",
				"params" => array(),
				"output" => false
			),
			array(
				"route" => "test",
				"params" => array(
					"foo" => "bar",
				),
				"output" => array(
					"routeType" => "test",
					"route" => "bar/foo",
					"method" => "GET",
					"params" => array(
						"foo" => "bar",
					),
					"headers" => array(),
					"data" => null,
				),
			),
		);
	}

	/**
	 * provider_Post provides different sets of data to test various cases within
	 * the post method
	 */
	public function provider_Post() {
		return array(
			array(
				"route" => "foo",
				"data" => null,
				"params" => array(),
				"output" => array(
					"routeType" => "foo",
					"route" => "foo/bar",
					"method" => "POST",
					"params" => array(),
					"headers" => array(),
					"data" => null,
				),
			),
			array(
				"route" => "bar",
				"data" => array(),
				"params" => array(),
				"output" => false
			),
			array(
				"route" => "test",
				"data" => array(
					"test" => "one",
				),
				"params" => array(
					"foo" => "bar",
				),
				"output" => array(
					"routeType" => "test",
					"route" => "bar/foo",
					"method" => "POST",
					"params" => array(
						"foo" => "bar",
					),
					"headers" => array(),
					"data" => array(
						"test" => "one",
					),
				),
			),
		);
	}

	/**
	 * provider_Put provides different sets of data to test various cases within
	 * the put method
	 */
	public function provider_Put() {
		return array(
			array(
				"route" => "foo",
				"data" => null,
				"params" => array(),
				"output" => array(
					"routeType" => "foo",
					"route" => "foo/bar",
					"method" => "PATCH",
					"params" => array(),
					"headers" => array(),
					"data" => null,
				),
			),
			array(
				"route" => "bar",
				"data" => array(),
				"params" => array(),
				"output" => false
			),
			array(
				"route" => "test",
				"data" => array(
					"test" => "one",
				),
				"params" => array(
					"foo" => "bar",
				),
				"output" => array(
					"routeType" => "test",
					"route" => "bar/foo",
					"method" => "PATCH",
					"params" => array(
						"foo" => "bar",
					),
					"headers" => array(),
					"data" => array(
						"test" => "one",
					),
				),
			),
		);
	}

	/**
	 * provider_Delete provides different sets of data to test various cases within
	 * the delete method
	 */
	public function provider_Delete() {
		return array(
			array(
				"route" => "foo",
				"params" => array(),
				"output" => array(
					"routeType" => "foo",
					"route" => "foo/bar",
					"method" => "DELETE",
					"params" => array(),
					"headers" => array(),
					"data" => null,
				),
			),
			array(
				"route" => "bar",
				"params" => array(),
				"output" => false
			),
			array(
				"route" => "test",
				"params" => array(
					"foo" => "bar",
				),
				"output" => array(
					"routeType" => "test",
					"route" => "bar/foo",
					"method" => "DELETE",
					"params" => array(
						"foo" => "bar",
					),
					"headers" => array(),
					"data" => null,
				),
			),
		);
	}

	/**
	 * provider_Head provides different sets of data to test various cases within
	 * the head method
	 */
	public function provider_Head() {
		return array(
			array(
				"route" => "foo",
				"params" => array(),
				"output" => array(
					"routeType" => "foo",
					"route" => "foo/bar",
					"method" => "HEAD",
					"params" => array(),
					"headers" => array(),
					"data" => null,
				),
			),
			array(
				"route" => "bar",
				"params" => array(),
				"output" => false
			),
			array(
				"route" => "test",
				"params" => array(
					"foo" => "bar",
				),
				"output" => array(
					"routeType" => "test",
					"route" => "bar/foo",
					"method" => "HEAD",
					"params" => array(
						"foo" => "bar",
					),
					"headers" => array(),
					"data" => null,
				),
			),
		);
	}
}
