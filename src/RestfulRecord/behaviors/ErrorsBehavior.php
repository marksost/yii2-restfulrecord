<?php

namespace RestfulRecord\behaviors;

use RestfulRecord\RestfulRecordRequest;
use yii\base\Behavior;
use yii\helpers\Arrayhelper;

/**
 * ErrorsBehavior adds error storage functionality to RestfulRecord
 * 
 * @package Behaviors
 */
class ErrorsBehavior extends Behavior {

	/**
	 * @var array An array of errors that occured during an API request
	 * 
	 * Populated via API responses when a non-200 error is returned
	 */
	protected $_errors = array();

	/**
	 * getErrors returns an array of errors that were recorded during API requests
	 * Takes an optional argument that specifies which attribute to get errors for
	 * NOTE: errors are stored based on attribute, ie:
	 * 	array(
	 * 		'attribute-name' => array(
	 * 			...array of errors here...
	 * 		),
	 * 	)
	 * 
	 * @param mixed 		If a string, will return only errors associated with that attribute
	 * 
	 * @return array 		Array of errors, either all of them or just those associated with the specified attribute
	 */
	public function getErrors( $attribute = null ) {
		// Check for null attribute label, and return all errors
		if ( is_null( $attribute ) ) {
			return $this->_errors;
		}

		// Check that attribute is defined and return it's errors, otherwise return an empty array
		return isset( $this->_errors[ $attribute ] ) ? $this->_errors[ $attribute ] : array();
	}

	/**
	 * getFirstError returns the first error of an attribute's set of errors
	 * 
	 * @param string $attribute 			Attribute name to be used for getting errors
	 * 
	 * @return mixed 				If the attribute has errors, returns the first one
	 * 						otherwise returns false
	 */
	public function getFirstError( $attribute ) {
		return isset( $this->_errors[ $attribute ] ) ? reset( $this->_errors[ $attribute ] ) : false;
	}

	/**
	 * getFirstErrorMessageByKey returns the message string contained in the first error array with the key of $key
	 *
	 * @param string $key 		The key the message is contained in within the error array
	 * @param string $default 	A fallback message, if the message key doesn't exist, defaults to an empty string
	 *
	 * @return string		A string, the value contained in the first error, found by $key
	 */
	public function getFirstErrorMessageByKey( $key = "", $default = "" ) {
		// Get the first error in the errors array
		$error = $this->getFirstError( "errors" );

		// Return the value of the message key
		return ArrayHelper::getValue( $error, $key, $default );
	}

	/**
	 * hasErrors check for errors being present
	 * Optionally checks if errors are present on a specific attribute
	 * 
	 * @param string $attribute 			Attribute name to be used for getting errors
	 * 
	 * @return boolean 				Returns true if errors are present, false otherwise
	 */
	public function hasErrors( $attribute = null ) {
		return is_null( $attribute ) ? !empty( $this->_errors ) : isset( $this->_errors[ $attribute ] );
	}

	/**
	 * Add error adds a new error to the specified attribute's error array
	 * 
	 * @param string $attribute 			Attribute name to be used for setting errors
	 * @param string $error 			Error to be set
	 * 
	 * @return RestfulRecord 			Returns a reference to `$this` to allow for chaining
	 */
	public function addError( $attribute, $error = '' ) {
		// Check if attribute already has an error array, otherwise initialize it
		if ( !isset( $this->_errors[ $attribute ] ) ) {
			$this->_errors[ $attribute ] = array();
		}

		// Add error
		$this->_errors[ $attribute ][] = $error;

		return $this->owner;
	}

	/**
	 * Add error adds new errors to a set of attribute's error arrays
	 * 
	 * @param array $errors 		Array of errors to be set
	 * 					Should follow the blueprint of:
	 * 					array(
	 * 						'attribute-name' => array(
	 * 							..array of errors here...
	 * 						),
	 * 					)
	 * 					OR
	 * 					array(
	 * 						'attribute-name' => 'Error message here',
	 * 					)
	 * 
	 * @return RestfulRecord 		Returns a reference to `$this` to allow for chaining
	 */
	public function addErrors( $errors = array() ) {
		// Loop through errors, setting attribute error arrays
		foreach ( $errors as $attribute => $errs ) {
			// Loop through array of errors, setting each one in turn
			if ( is_array( $errs ) ) {
				foreach ( $errs as $error ) {
					$this->addError( $attribute, $error );
				}
			// Set single error
			} else {
				$this->addError( $attribute, $errs );
			}
		}

		return $this->owner;
	}

	/**
	 * setErrors  resets the array of errors and replaces it with the calling argument.
	 * 
	 * @param array $errors 		The array of errors to set as `$this->_errors`
	 * 
	 * @return RestfulRecord 		Returns a reference to `$this` to allow for chaining
	 */
	public function setErrors( $errors = array() ) {
		$this->_errors = $errors;

		return $this->owner;
	}

	/**
	 * clearErrors either resets the entire errors array, or clears just those errors that are
	 * associated with a specific attribute
	 * 
	 * @param string $attribute 			If present, this function will only clear that attribute's errors
	 * 
	 * @return RestfulRecord 			Returns a reference to `$this` to allow for chaining
	 */
	public function clearErrors( $attribute = null ) {
		// Check for attribute, otherwise reset all errors
		if ( is_null( $attribute ) ) {
			$this->_errors = array();
		// Check that attribute has an errors array, and unset it if so
		} else if ( isset( $this->_errors[ $attribute ] ) ) {
			unset( $this->_errors[ $attribute ] );
		}

		return $this->owner;
	}
}
