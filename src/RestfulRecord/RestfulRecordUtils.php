<?php

namespace RestfulRecord;

use yii\helpers\ArrayHelper;

/**
 * RestfulRecordUtils is a RestfulRecord utility class used to abstract various utility functions
 * out of the main codebase to keep the RestfulRecord class more focusses
 * 
 * RestfulRecord is a Yii component that provides an ActiveRecord-like interface
 * for interacting with an API (instead of a database).
 * 
 * @package RestfulRecord
 */
class RestfulRecordUtils {
	/**
	 * @var RestfulRecord An instance of the model this class is attached to
	 * 
	 * NOTE: Needed for calling model-level functions
	 */
	private $_restfulRecord;

	// Begin PHP magic method overrides
		public function __construct( RestfulRecord $restfulRecord ) {
			// Set internal restful record instance
			$this->setRestfulRecord( $restfulRecord );
		}
	// End PHP magic method overrides

	// Begin Getters and Setters
		/**
		 * getRestfulRecord returns this class' `_restfulRecord` property
		 * 
		 * @return RestfulRecord 			This class' `_restfulRecord` property
		 */
		public function getRestfulRecord() {
			return $this->_restfulRecord;
		}

		/**
		 * setRestfulRecord sets this class' `_restfulRecord` property
		 * 
		 * @param RestfulRecord 			Class to be set as this class' `_restfulRecord` property
		 * 
		 * @return RestfulRecordUtils 			Returns a reference to `$this` to allow for chaining
		 */
		protected function setRestfulRecord( RestfulRecord $restfulRecord ) {
			$this->_restfulRecord = $restfulRecord;

			return $this;
		}
	// End Getters and Setters

	// Begin Utility methods
		/**
		 * attachClassAliases attaches additional class aliases that may be used to re-declare classes
		 * Classes should be re-declared if they need to be accessed via variables
		 * NOTE: when re-declaring class names with the same class name, be sure to suppress warnings
		 * 
		 * Example:
		 * 	@class_alias( "\Some\Path\Foo", "Foo" );
		 * 
		 * 	This will allow for:
		 * 		$className = "Foo";
		 * 		$class = new $className();
		 * 
		 * 	Without the above class alias declaration, the above will throw a "class not found" error
		 * 	You would need to have done:
		 * 		$className = "\Some\Path\Foo";
		 * 		$class = new $className();
		 */
		public function attachClassAliases() {
			foreach ( $this->getRestfulRecord()->classAliases as $namespace => $alias ) {
				@class_alias( $namespace, $alias );
			}
		}

		/**
		 * attachEvents loops through defined events, attaching them to this class as needed
		 * NOTE: see [[events]] for information on how event array should be shaped
		 * 
		 * @param array $events 			An array of zero or more events to attach
		 */
		public function attachEvents( array $events = array() ) {
			// Loop through events, attaching each one in turn
			foreach ( $events as $event ) {
				// Check for valid event
				if( count( $event ) !== 2 ) {
					continue;
				}

				// Attach event
				$this->getRestfulRecord()->on( $event[ 0 ], $event[ 1 ] );
			}
		}

		/**
		 * filterNullValues will filter out null or empty strings if the configuration says to do so
		 * 
		 * @param array $values 			Array of values to be filtered
		 * 
		 * @return array 				An array of filtered values
		 */
		public function filterNullValues( $values ) {
			// Check if the configuration says to filter out null values
			if ( !$this->getRestfulRecord()->restConfig()[ "allowNullValues" ] ) {
				// Check for valid input
				if ( !is_array( $values) ) return $values;

				// Set up empty return value
				$temp = array();

				// Check to see if we need to exclude some of the attributes from the null filter
				$keyExcludes = $this->getAllowedNullAttributes();

				foreach ( $values as $key => $value ) {
					// Check for empty strings or null values
					if ( ( $value !== "" && $value !== null ) || in_array( $key, $keyExcludes ) ) {
						// Recursively call this function to filter through arrays if needed
						$temp[ $key ] = $this->filterNullValues( $value );
					}
				}

				return $temp;
			}

			return $values;
		}

		/**
		 * filterParams is used to remove blacklisted params from a request parameter array
		 * before it gets sent to an API
		 * 
		 * @param array $params     An array of request parameters to filter
		 * 
		 * @return array            A filtered request parameter array
		 */
		public function filterParams( array $params = array() ) {
			// Get rest config
			$restConfig = $this->getRestfulRecord()->restConfig();

			// Get blacklisted params from rest config
			$blacklistedParams = ArrayHelper::getValue( $restConfig, "blacklistedParams", array() );

			// Loop blacklisted params, removing any from request params
			foreach ( $blacklistedParams as $key ) {
				ArrayHelper::remove( $params, $key );
			}

			return $params;
		}

		/**
		 * getAllowedNullAttributes will check to see if the allowedNullAttributes key is set in the restConfig
		 * and, if it is, return it.
		 *
		 * This is used to allow some attributes to be sent to the API as null
		 * 
		 * @return array 				An array of model attribute names, ex. array( "publish_from" )
		 */
		protected function getAllowedNullAttributes() {
			// If the rest config "allowedNullAttributes" is set, return that array
			return ( !empty( $this->getRestfulRecord()->restConfig()[ "allowedNullAttributes" ] ) ) ? $this->getRestfulRecord()->restConfig()[ "allowedNullAttributes" ] : array();
		}

		/**
		 * replaceMacros takes a string representing a route and replaces a set of defined macros
		 * with there model-specific values
		 * 
		 * @param string $str        A string representing a route with macros to be replaced
		 * @param array $options     An array of request-based options to be passed to the macro getter method
		 * @param string $method     A string indicating the macro getter method to use
		 * 
		 * @return string            The route string with macros replaced
		 */
		public function replaceMacros( $str = "", array $options = array(), $method = "getUrlMacros" ) {
			// Check for valid input
			if ( empty( $str ) ) {
				return '';
			}

			// Replace all defined macros
			return strtr( $str, $this->getRestfulRecord()->{ $method }( $options ) );
		}
	// End Utility methods
}
