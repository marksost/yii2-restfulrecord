<?php

namespace RestfulRecord\behaviors;

use RestfulRecord\RestfulRecordRequest;
use yii\base\Behavior;

/**
 * ApiResponsesBehavior adds API response storage functionality to RestfulRecord
 * 
 * @package Behaviors
 */
class ApiResponsesBehavior extends Behavior {

	/**
	 * @var array An array of responses from the API.
	 * 
	 * Useful for getting raw request data from the API to shed more light on errors
	 */
	protected $_apiResponses = array();

	/**
	 * getApiResponse returns a single API response from the array of responses stored in the model.
	 * Takes an index as a parameter. If the index exists within the array of responses,
	 * that response will be returned. Defaults to the last response in the array.
	 * Useful for getting the raw response from the API
	 * 
	 * @param integer $index 		Index of the response to be retrived
	 * 
	 * @return mixed 			Returns an API response if there is one, false otherwise
	 */
	public function getApiResponse( $index = 0 ) {
		// Check for at least one response
		if ( empty( $this->_apiResponses ) ) {
			return false;
		}

		// Check that index exists, or defaults to the last response in the array
		if ( empty( $index ) || !isset( $this->_apiResponses[ $index ] ) ) {
			$index = isset( $this->_apiResponses[ count( $this->_apiResponses ) - 1 ] ) ?
				count( $this->_apiResponses ) - 1 : 0;
		}

		return $this->_apiResponses[ $index ];
	}

	/**
	 * getApiResponses returns all API responses stored in the model
	 * 
	 * @return array 		The array of API responses
	 */
	public function getApiResponses() {
		return $this->_apiResponses;
	}

	/**
	 * addApiResponse inserts an API response into the array of API responses.
	 * 
	 * @param mixed 			The response to add
	 * 
	 * @return RestfulRecord 		Returns a reference to `$this` to allow for chaining
	 */
	public function addApiResponse( $response ) {
		$this->_apiResponses[] = $response;

		return $this->owner;
	}

	/**
	 * setApiResponse resets the array of API responses and replaces it with the calling argument.
	 * 
	 * @param array $responses 			The array of responses to set as `$this->_apiResponses`
	 * 
	 * @return RestfulRecord 			Returns a reference to `$this` to allow for chaining
	 */
	public function setApiResonses( array $responses = array() ) {
		$this->_apiResponses = $responses;

		return $this->owner;
	}
}
