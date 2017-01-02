<?php

namespace RestfulRecord\behaviors;

use GuzzleHttp\Psr7\Response;
use RestfulRecord\RestfulRecordRequest;
use RestfulRecord\log\Logger;
use yii\base\Behavior;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use Yii;

/**
 * CrudBehavior adds API CRUD functionality to RestfulRecord
 * 
 * @package Behaviors
 */
class CrudBehavior extends Behavior {

	/**
	 * @var RestfulRecordRequest A reference to the current request being used
	 */
	protected $_request;

	// Begin Getters and Setters
		/**
		 * getRequest returns this class' `_request` property
		 * 
		 * @return RestfulRecordRequest 		This class' `_request` property
		 */
		public function getRequest() {
			return $this->_request;
		}

		/**
		 * setRequest sets this class' `_request` property
		 * 
		 * @param RestfulRecordRequest 		Class to be set as this class' `_request` property
		 * 
		 * @return RestfulRecord 			Returns a reference to `$this->owner` to allow for chaining
		 */
		protected function setRequest( RestfulRecordRequest $request = null ) {
			$this->_request = $request;

			return $this->owner;
		}
	// End Getters and Setters

	// Begin CRUD methods
		/**
		 * get sends a GET request to the API
		 * 
		 * @param string $route 		The type of route to use
		 * 					NOTE: the route should correspond with one defined in the [[routes()]] method
		 * @param array $params 		Array of query string variables to be sent with the request (ex: ?limit=X)
		 * 
		 * @return array 			Array representing a decoded API response
		 */
		public function get( $route = "", array $params = array() ) {
			// Build query info based on this request
			$info = $this->buildQueryInfo( array(
				"routeType" => $route,
				"route" => $route,
				"params" => $params,
				"method" => "GET",
			) );

			// Query the API and return the JSON-decoded response
			return $this->query( $info );
		}

		/**
		 * post sends a POST request to the API
		 * 
		 * @param string $route 		The type of route to use
		 * 					NOTE: the route should correspond with one defined in the [[routes()]] method
		 * @param array $data 			Array of attributes to be sent in the request body (after JSON encoding)
		 * @param array $params 		Array of query string variables to be sent with the request (ex: ?limit=X)
		 * 
		 * @return array 			Array representing a decoded API response
		 */
		public function post( $route = "", array $data = null, array $params = array() ) {
			// Build query info based on this request
			$info = $this->buildQueryInfo( array(
				"routeType" => $route,
				"route" => $route,
				"params" => $params,
				"method" => "POST",
				"data" => $data,
			) );

			// Query the API and return the JSON-decoded response
			return $this->query( $info );
		}

		/**
		 * put sends a PUT request to the API
		 * 
		 * @param string $route 		The type of route to use
		 * 					NOTE: the route should correspond with one defined in the [[routes()]] method
		 * @param array $data 			Array of attributes to be sent in the request body (after JSON encoding)
		 * @param array $params 		Array of query string variables to be sent with the request (ex: ?limit=X)
		 * @param string $method 		The method to use (can be PUT or PATCH)
		 * 					NOTE: This is used to determine if a full model should be sent, or just changed attributes
		 * 
		 * @return array 			Array representing a decoded API response
		 */
		public function put( $route = "", array $data = null, array $params = array(), $method = "PATCH" ) {
			// Build query info based on this request
			$info = $this->buildQueryInfo( array(
				"routeType" => $route,
				"route" => $route,
				"params" => $params,
				"method" => $method,
				"data" => $data,
			) );

			// Query the API and return the JSON-decoded response
			return $this->query( $info );
		}

		/**
		 * delete sends a DELETE request to the API
		 * 
		 * @param string $route 		The type of route to use
		 * 					NOTE: the route should correspond with one defined in the [[routes()]] method
		 * @param array $params 		Array of query string variables to be sent with the request (ex: ?limit=X)
		 * 
		 * @return array 			Array representing a decoded API response
		 */
		public function delete( $route = "", array $params = array() ) {
			// Build query info based on this request
			$info = $this->buildQueryInfo( array(
				"routeType" => $route,
				"route" => $route,
				"params" => $params,
				"method" => "DELETE",
			) );

			// Query the API and return the JSON-decoded response
			return $this->query( $info );
		}

		/**
		 * head sends a HEAD request to the API
		 *
		 * @param string $route     The type of route to use
		 *                          NOTE: the route should correspond with one defined in the [[routes()]] method
		 * @param array $params     Array of query string variables to be sent with the request (ex: ?limit=X)
		 *
		 * @return array            Array representing a decoded API response
		 */
		public function head( $route = "", array $params = array() ) {
			// Build query info based on this request
			$info = $this->buildQueryInfo( array(
				"routeType" => $route,
				"route" => $route,
				"params" => $params,
				"method" => "HEAD",
			) );

			// Query the API and return the JSON-decoded response
			return $this->query( $info );
		}
	// End CRUD methods

	// Begin Query methods
		/**
		 * query creates a new request object and returns the result of it's internal query function
		 * Sets internal request properties based on the information contained in the calling argument
		 * 
		 * @param array $info 			Information specific to the request to be sent
		 * 
		 * @return array 			Array representing a decoded API response
		 */
		protected function query( $info = array() ) {
			// Check for valid input
			if ( empty( $info ) ) {
				return null;
			}

			// Store owner
			$owner = $this->owner;

			// Get method from info
			$method = ArrayHelper::getValue( $info, "method", "GET" );

			// Create a new request
			$this->setRequest( $this->getNewRequest() );

			// Configure cache behavior
			$owner->configureCache( $info );

			// Check if caching is supported and a response is found
			if ( $owner->shouldUseCache() && ( $response = $owner->getResponseFromCache() ) !== false ) {
				// Store response in stream object
				$response = new Response( 200, array(), $response );

				// Set error to false
				$error = false;
			} else {
				// Set request properties
				$this->getRequest()->setBaseUrl( $owner->restConfig()[ "apiUrl" ] )
					->setPath( ArrayHelper::getValue( $info, "route", "" ) )
					->setMethod( $method )
					->setParams( $owner->getUtils()->filterParams( ArrayHelper::getValue( $info, "params", array() ) ) )
					->setHeaders( ArrayHelper::getValue( $info, "headers", array() ) )
					->setData( ArrayHelper::getValue( $info, "data", null ) );

				// Query the API and return whether an error occurred as well as the API response
				list( $error, $response ) = $this->getRequest()->query();
			}
			
			// Add API response to owner responses
			$owner->addApiResponse( $response );

			// This can occur for unavilable APIs
			if ( !method_exists( $response, "getStatusCode" ) ) {
				// Set API errors
				$owner->setErrors( array( "API" => array( "Invalid API response." ) ) );

				return null;
			}

			// Try and JSON-decode response
			try {
				$response = Json::decode( $response->getBody()->getContents() );
			} catch ( InvalidParamException $e ) {
				$response = "";
			}

			// Check for 400+ response status codes, log response
			if ( $owner->getApiResponse()->getStatusCode() >= 400 ) {
				Logger::logMessage( "error", "400+ response detected. Response was: ".Json::encode( $response ) );
			}

			// Check for valid response
			if ( $error === false ) {
				return $response;
			}

			// Set API errors
			$owner->setErrors( $response );

			return null;
		}
	// End Query methods

	// Begin Utility methods
		/**
		 * buildQueryInfo takes in an array of options and builds a properly-formed information array
		 * to be used by an API request class
		 * 
		 * @param array $options 		An array of options that may override the default settings
		 * 
		 * @return array 			A properly-formed information array
		 * 					to be used by an API request class
		 * 					NOTE: Returns false for invalid routes
		 */
		public function buildQueryInfo( array $options = array() ) {
			// Provide defaults and allow them to be overridden
			$options = ArrayHelper::merge( array(
				"routeType" => "",
				"route" => "",
				"params" => array(),
				"headers" => array(),
				"method" => "GET",
				"data" => null,
			) , $options );

			// Get all defined routes
			$routes = $this->owner->routes();

			// Get route
			$route = ArrayHelper::getValue( $routes, $options[ "route" ], false );

			// Check the requested route is defined by the routes function
			if( $route === false ) {
				return false;
			}

			// Replace route macros with data from this class
			$options[ "route" ] = $this->owner->getUtils()->replaceMacros( $routes[ $options[ "route" ] ], $options );

			return $options;
		}

		/**
		 * getNewRequest returns a new initialized request object to query
		 * Abstracted here to allow for class-level overrides and easier mocking
		 * 
		 * @return RestfulRecordRequest 		A new, initalized request object
		 */
		protected function getNewRequest() {
			return new RestfulRecordRequest( $this->owner->getComponent() );
		}
	// End Utility methods
}
