<?php

namespace RestfulRecord\behaviors;

use yii\base\Behavior;

/**
 * ResourceBehavior adds resource/collection creation functionality to RestfulRecord
 * 
 * @package Behaviors
 */
class ResourceBehavior extends Behavior {

	// Begin Creation methods
		/**
		 * createResource takes an API response and returns a newly-instantiated model based on the data
		 * within that response
		 * 
		 * @param array $response 			An API response to be used to create a new model
		 * @param boolean $fromCollection 		Indicates if the response to parse has come from a collection request or not
		 * 						NOTE: This determines if the method should check for an items container key or not
		 * 
		 * @return mixed 				If the API response is properly formed, a newly-instantiated model
		 * 						otherwise null
		 */
		public function createResource( $response = array(), $fromCollection = false ) {
			// Store the original response before manipulation
			$originalResponse = $response;

			// Store the rest config for reuse later
			$restConfig = $this->owner->restConfig();

			// Check if the response contains an error key as defined by the configuration
			// NOTE: This can occur for various API operations while still returning a 200..
			if ( isset( $response[ $restConfig[ "errorKey" ] ] ) ) {
				// Set API errors
				$this->owner->setErrors( $response );

				return null;
			}

			// Check if we're dealing with a resource sent from a collection request
			// If not, parse out the resource based on the items container key set in the application configuration
			if ( $fromCollection !== true ) {
				// Check for resource to contain items container key
				if ( isset( $response[ $restConfig[ "itemsContainerKey" ] ] ) ) {
					$response = $response[ $restConfig[ "itemsContainerKey" ] ];
				} else {
					return null;
				}
			}

			// Check for a valid response
			if ( !empty( $response ) && is_array( $response ) ) {
				// Instantiate a new version of this class
				$resource = $this->instantiate();

				// Set the resource's attributes
				foreach ( $response as $key => $value ) {
					$resource->setAttribute( $key, $value );
				}

				// Add API response to array of responses
				$resource->addApiResponse( $originalResponse );

				return $resource;
			}

			return null;
		}

		/**
		 * createCollection takes an API response and returns an array of newly-instantiated models based on the data
		 * within that response
		 * 
		 * @param array $response 			An API response to be used to create new models
		 * 
		 * @return array 				An array of zero or more newly-instantiated models
		 */
		public function createCollection( $response = array() ) {
			// Store the original response before manipulation
			$originalResponse = $response;

			// Store the rest config for reuse later
			$restConfig = $this->owner->restConfig();

			// Set up empty return value
			$resources = array();

			// Check that the response contains an items property as defined by the configuration
			if ( !empty( $response) && isset( $response[ $restConfig[ "itemsContainerKey" ] ] ) ) {
				$response = $response[ $restConfig[ "itemsContainerKey" ] ];
			} else {
				return $resources;
			}

			// Loop through items in the response
			foreach ( $response as $item ) {
				// Call `createResource` on each
				if ( ( $resource = $this->createResource( $item, true ) ) !== null ) {
					// Add resource to the return array
					$resources[] = $resource;

					// Reset api responses to use the original response
					$resource->setApiResonses( array( $originalResponse ) );
				}
			}

			return $resources;
		}
	// End Creation methods

	// Begin Utility methods
		/**
		 * instantiate create a new instance of this class
		 * Called by [[createResource()]] to create a new model baed on an API response
		 * 
		 * @return RestfulRecord 		A new instance of this class
		 */
		protected function instantiate() {
			$class = get_class( $this->owner );

			return new $class();
		}
	// End Utility methods
}
