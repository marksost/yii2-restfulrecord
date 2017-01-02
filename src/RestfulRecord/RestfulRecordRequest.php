<?php

namespace RestfulRecord;

use Concat\Http\Middleware\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use RestfulRecord\log\Logger as RestfulRecordLogger;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * RestfulRecordRequest makes Guzzle HTTP requests based on a set of internal variables.
 * 
 * This class defines a single Guzzle request, it should only be used once
 * 
 * RestfulRecord is a Yii component that provides an ActiveRecord-like interface
 * for interacting with an API (instead of a database).
 * 
 * @package RestfulRecord
 */
class RestfulRecordRequest extends Component {
	
	/**
	 * @var Client Internal Guzzle client
	 */
	protected $_client;

	/**
	 * @var Logger Logger for Guzzle requests
	 */
	protected $_logger;

	/**
	 * @var Request Internal Guzzle request
	 */
	protected $_request;

	/**
	 * @var RestfulrecordComponent Component as defined in the configuration
	 * 
	 * Allows this class to stay clear of configuration values but still tie into config files
	 */
	protected $_component;

	// Begin PHP magic method overrides
		public function __construct( RestfulRecordComponent $component, $baseUrl = "" ) {
			// Set up Yii component
			$this->setComponent( $component );
		}
	// End PHP magic method overrides

	// Begin Top-level methods
		/**
		 * behaviors add additional, encapsulated logic to this class without needing to
		 * be placed within it
		 * For more information, see http://www.yiiframework.com/doc-2.0/guide-concept-behaviors.html
		 * 
		 * @return array 		An array of zero or more behaviors to attach to this class
		 */
		public function behaviors() {
			return ArrayHelper::merge( parent::behaviors(), array(
				"request-behavior" => "RestfulRecord\behaviors\RequestBehavior",
			) );
		}

		/**
		 * query forms a Guzzle request, executes it, and returns the result
		 * 
		 * @return array 		An array of data representing an API response
		 */
		public function query() {
			// Get client
			$client = $this->getClient();

			// Get request
			$request = $this->getRequest();

			// Try and make the request, passing in request options
			try {
				return array( false, $client->send( $request, $this->formRequestOptions() ) );
			// NOTE: All guzzle exceptions extend RequestException
			} catch( RequestException $e ) {
				return array( true, $e->hasResponse() ? $e->getResponse() : $e->getMessage() );
			}
		}
	// End Top-level methods

	// Begin Getters and Setters
		/**
		 * createClient sets up and returns a new Guzzle client
		 * for making requests
		 * 
		 * @return Client 			A new Guzzle client
		 */
		protected function createClient() {
			// Form new handler stack
			// NOTE: Using create here to keep current stack intact
			$stack = HandlerStack::create();

			// Check for logging
			$enableLogging = ( bool )ArrayHelper::getValue( $this->getComponent()->attributes, "enableLogging", false );

			// Only add logging middleware if logging is enabled
			if ( $enableLogging ) {
				// Push logging middleware
				$stack->push( $this->getLogger(), "logger" );
			}

			return new Client( array(
				"base_uri" => $this->getBaseUrl(),
				"handler" => $stack,
			) );
		}

		/**
		 * getClient returns this class' `_client` property
		 * 
		 * @return Request 		This class' `_client` property
		 */
		public function getClient() {
			// Create client if not already set
			if ( is_null( $this->_client ) ) {
				$this->setClient( $this->createClient() );
			}

			return $this->_client;
		}

		/**
		 * setClient sets this class' `_client` property
		 * 
		 * @param Request $client 				Class to be set as this class' `_client` property
		 * 
		 * @return RestfulRecordRequest 		Returns a reference to `$this` to allow for chaining
		 */
		protected function setClient( $client ) {
			$this->_client = $client;

			return $this;
		}

		/**
		 * getComponent returns this class' `_component` property
		 * 
		 * @return string 		This class' `_component` property
		 */
		public function getComponent() {
			return $this->_component;
		}

		/**
		 * setComponent sets this class' `_component` property
		 * 
		 * @param RestfulRecordComponent $component 		Yii component to be set as this class' `_component` property
		 * 
		 * @return RestfulRecordRequest 				Returns a reference to `$this` to allow for chaining
		 */
		protected function setComponent( RestfulRecordComponent $component ) {
			$this->_component = $component;

			return $this;
		}

		/**
		 * createLogger sets up and returns a new Logger instance
		 * for logging request data
		 * 
		 * @return Logger 			A new Logger
		 */
		protected function createLogger() {
			return new Logger( array( $this->createRestfulRecordLogger(), "log" ) );
		}

		/**
		 * getLogger returns this class' `_logger` property
		 * 
		 * @return Logger 		This class' `_logger` property
		 */
		public function getLogger() {
			// Set up request if not already set
			if ( is_null( $this->_logger ) ) {
				$this->setLogger( $this->createLogger() );
			}

			return $this->_logger;
		}

		/**
		 * setLogger sets this class' `_logger` property
		 * 
		 * @param Logger $logger 				Class to be set as this class' `_logger` property
		 * 
		 * @return RestfulRecordRequest 		Returns a reference to `$this` to allow for chaining
		 */
		protected function setLogger( $logger ) {
			$this->_logger = $logger;

			return $this;
		}

		/**
		 * createRestfulRecordLogger sets up a custom logger that will
		 * proxy logs to Yii's internal logger
		 * 
		 * @return RestfulRecordLogger 		A custom logger
		 */
		protected function createRestfulRecordLogger() {
			return new RestfulRecordLogger();
		}

		/**
		 * createRequest sets up and returns a new Guzzle request
		 * 
		 * @return Request 			A new Guzzle request
		 */
		protected function createRequest() {
			// Return a new request object
			return new Request( $this->getMethod(), $this->getBaseUrl().$this->getPath() );
		}

		/**
		 * getRequest returns this class' `_request` property
		 * 
		 * @return Request 		This class' `_request` property
		 */
		public function getRequest() {
			// Set up request if not already set
			if ( is_null( $this->_request ) ) {
				$this->setRequest( $this->createRequest() );
			}

			return $this->_request;
		}

		/**
		 * setRequest sets this class' `_request` property
		 * 
		 * @param Request $request 				Class to be set as this class' `_request` property
		 * 
		 * @return RestfulRecordRequest 		Returns a reference to `$this` to allow for chaining
		 */
		protected function setRequest( $request ) {
			$this->_request = $request;

			return $this;
		}
	// End Getters and Setters

	// Begin Utility methods
		/**
		 * formRequestOptions forms request options to be sent to the client
		 * using internal getters
		 * 
		 * @return array 		An array of request options to be sent to the client
		 */
		protected function formRequestOptions() {
			// Set up Guzzle request options array
			$options = ArrayHelper::merge( array(
				"allow_redirects" => true,
				"bodyKey" => "json",
				"connect_timeout" => ( int )$this->getComponent()->attributes[ "timeout" ],
				"headers" => $this->getHeaders(),
				"query" => $this->getParams(),
				"timeout" => ( int )$this->getComponent()->attributes[ "timeout" ],
				"synchronous" => true,
			), $this->getComponent()->attributes[ "requestOptions" ] );

			// Check for any set data
			if ( !empty( $this->getData() ) ) {
				$options[ $options[ "bodyKey" ] ] = $this->getData();
			}

			// Unset bodyKey
			unset( $options[ "bodyKey" ] );

			return $options;
		}
	// End Utility methods
}
