<?php

namespace src\RestfulRecord;

use Codeception\Util\Stub;
use Concat\Http\Middleware\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use RestfulRecord\RestfulRecordComponent;
use RestfulRecord\RestfulRecordRequest;
use RestfulRecord\log\Logger as RestfulRecordLogger;

/**
 * @coversDefaultClass RestfulRecord\RestfulRecordRequest
 */
class RestfulRecordRequestTest extends \Codeception\TestCase\Test {

	// An instance of the request to be tested
	private $_request;

	// A mock of RestfulRecordComponent
	private $_mockRestfulRecordComponent;

	// Codeception tester
	protected $tester;

	protected function _before() {
		// Set up mock
		$this->_mockRestfulRecordComponent = Stub::make( "RestfulRecord\RestfulRecordComponent" );

		// Set up request
		$this->_request = new RestfulRecordRequest( $this->_mockRestfulRecordComponent );
	}

	/**
	 * test__Construct tests that the __construct method
	 * properly sets a passed-in component
	 *
	 * @covers ::__construct
	 */
	public function test__Construct() {
		// Verify a component was set in the constructor
		verify( $this->_request->getComponent() )->notNull();
		verify( $this->_request->getComponent() )->equals( $this->_mockRestfulRecordComponent );
	}

	/**
	 * test__BehaviorHasKeys tests that the behavior method
	 * properly sets various behaviors
	 *
	 * @covers ::behaviors
	 */
	public function test__BehaviorHasKeys() {
		// Cache behavior keys
		$keys = array_keys( $this->_request->behaviors() );

		// Verify behaviors contain various keys
		verify( $keys )->contains( "request-behavior" );
	}

	/**
	 * test__QueryReturnsError tests that the query method
	 * properly returns an error when one occurs
	 * 
	 * @covers ::query
	 */
	public function test__QueryReturnsError() {
		// Mock request
		$request = Stub::make( "RestfulRecord\RestfulRecordRequest", array(
			"_component" => $this->_mockRestfulRecordComponent,
			"getClient" => function() {
				return Stub::make( "GuzzleHttp\Client", array(
					"send" => function( RequestInterface $request, array $options = array() ) {
						throw new RequestException( "foo", $request );
					}
				) );
			}
		) );

		// Verify an error was caught and returned
		verify( $request->query() )->equals( array( true, "foo" ) );
	}

	/**
	 * test__Query tests that the query method
	 * properly returns a non-error when send is successfull
	 * 
	 * @covers ::query
	 */
	public function test__Query() {
		// Mock request
		$request = Stub::make( "RestfulRecord\RestfulRecordRequest", array(
			"_component" => $this->_mockRestfulRecordComponent,
			"getClient" => function() {
				return Stub::make( "GuzzleHttp\Client", array(
					"send" => function( RequestInterface $request, array $options = array() ) {
						return "foo";
					}
				) );
			}
		) );

		// Verify an no error occurred
		verify( $request->query() )->equals( array( false, "foo" ) );
	}

	/**
	 * test__GettersAndSetters tests various getter and setter methods
	 * 
	 * @covers ::getClient
	 * @covers ::setClient
	 * @covers ::getComponent
	 * @covers ::setComponent
	 * @covers ::getLogger
	 * @covers ::setLogger
	 * @covers ::getRequest
	 * @covers ::setRequest
	 */
	public function test__GettersAndSetters() {
		$getters = array(
			"getClient" => array(
				"default" => Stub::make( "GuzzleHttp\Client" ),
				"setter" => "setClient",
			),
			"getComponent" => array(
				"default" => Stub::make( "RestfulRecord\RestfulRecordComponent" ),
				"setter" => "setComponent",
			),
			"getLogger" => array(
				"default" => Stub::make( "Concat\Http\Middleware\Logger" ),
				"setter" => "setLogger",
			),
			"getRequest" => array(
				"default" => Stub::make( "GuzzleHttp\Psr7\Request" ),
				"setter" => "setRequest",
			),
		);

		foreach ( $getters as $getter => $arr ) {
			// Reset value
			$this->tester->invokeMethod( $this->_request, $arr[ "setter" ], array( $arr[ "default" ] ) );

			// Verify previous class object
			verify( $this->_request->{ $getter }() )->equals( $arr[ "default" ] );
		}
	}

	/**
	 * test__GetsSetProperties tests that getters set properties if not already set
	 * 
	 * @covers ::getClient
	 * @covers ::getLogger
	 * @covers ::getRequest
	 */
	public function test__GetsSetProperties() {
		$getters = array(
			"getClient" => "_client",
			"getLogger" => "_logger",
			"getRequest" => "_request",
		);

		foreach ( $getters as $getter => $property ) {
			// Verify property isn't set
			verify( $this->tester->reflectProperty( $this->_request, $property )->getValue( $this->_request ) )->null();

			// Call method
			$this->_request->{ $getter }();

			// Verify property is set
			verify( $this->tester->reflectProperty( $this->_request, $property )->getValue( $this->_request ) )->notNull();
		}
	}

	/**
	 * test__CreateClient tests that the createClient method
	 * returns a newly-created Client
	 * 
	 * @covers ::createClient
	 */
	public function test__CreateClient() {
		// Mock request
		$request = Stub::make( "RestfulRecord\RestfulRecordRequest", array(
			"_component" => Stub::make( "RestfulRecord\RestfulRecordComponent", array(
				"attributes" => array(
					"enableLogging" => true,
				)
			) ),
		) );

		// Store response from method call
		$client = $this->tester->invokeMethod( $request, "createClient" );

		// Get handler
		$handler = $client->GetConfig( "handler" );

		// Get handler stack
		$stack = $this->tester->reflectProperty( $handler, "stack" )->getValue( $handler );

		// Verify client is returned 
		verify( $client instanceOf Client )->true();

		// Verify logger was appended to the stack
		verify( $stack[ count( $stack ) - 1 ][ 1 ] )->equals( "logger" );
	}

	/**
	 * test__CreateLogger tests that the createLogger method
	 * returns a newly-created Logger
	 * 
	 * @covers ::createLogger
	 */
	public function test__CreateLogger() {
		// Verify logger is returned 
		verify( $this->tester->invokeMethod( $this->_request, "createLogger" ) instanceOf Logger )->true();
	}

	/**
	 * test__CreateRestfulRecordLogger tests that the createRestfulRecordLogger method
	 * returns a newly-created RestfulRecordLogger
	 * 
	 * @covers ::createRestfulRecordLogger
	 */
	public function test__CreateRestfulRecordLogger() {
		// Verify logger is returned 
		verify( $this->tester->invokeMethod( $this->_request, "createRestfulRecordLogger" ) instanceOf RestfulRecordLogger )->true();
	}

	/**
	 * test__CreateRequest tests that the createRequest method
	 * returns a newly-created Request
	 * 
	 * @covers ::createRequest
	 */
	public function test__CreateRequest() {
		// Verify request is returned 
		verify( $this->tester->invokeMethod( $this->_request, "createRequest" ) instanceOf Request )->true();
	}

	/**
	 * test__FormRequestOptions tests that the formRequestOptions method
	 * properly returns an array of request options
	 * 
	 * @covers ::formRequestOptions
	 */
	public function test__FormRequestOptions() {
		// Set request data
		$this->_request->setHeaders( array( "foo" => "bar" ) );
		$this->_request->setParams( array( "bar" => "baz" ) );
		$this->_request->setData( array( "test" => "one" ) );

		// Set expected output
		$output = array(
			"allow_redirects" => true,
			"connect_timeout" => 30,
			"headers" => array( "foo" => "bar" ),
			"query" => array( "bar" => "baz" ),
			"timeout" => 30,
			"synchronous" => true,
			"json" => array( "test" => "one" ),
		);

		// Verify output matches expected
		verify( $this->tester->invokeMethod( $this->_request, "formRequestOptions" ) )->equals( $output );
	}
}
