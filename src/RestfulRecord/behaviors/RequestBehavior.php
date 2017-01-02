<?php

namespace RestfulRecord\behaviors;

use yii\base\Behavior;
use yii\helpers\ArrayHelper;

/**
 * RequestBehavior adds various parameter functionality to RestfulRecordRequest
 * 
 * @package Behaviors
 */
class RequestBehavior extends Behavior {
	
	/**
	 * @var string Base URL of the API (ex: http://api.hearst.com)
	 * 
	 * NOTE: should NOT have a trailing slash
	 */
	protected $_baseUrl = "";

	/**
	 * @var string Path of the request (ex: /v1/content)
	 */
	protected $_path = "";

	/**
	 * @var string HTTP method of the request
	 */
	protected $_method = "GET";

	/**
	 * @var array Query string variables to be sent with the Guzzle request
	 */
	protected $_params = array();

	/**
	 * @var array HTTP headers to be sent with the Guzzle request
	 */
	protected $_headers = array();

	/**
	 * @var mixed Request body data to be sent with the Guzzle request
	 */
	protected $_data = null;

	/**
	 * getBaseUrl returns this class' `_baseUrl` property
	 * 
	 * @return string 		This class' `_baseUrl` property
	 */
	public function getBaseUrl() {
		return $this->_baseUrl;
	}

	/**
	 * setBaseUrl sets this class' `_baseUrl` property
	 * 
	 * @param string $baseUrl 			String to be set as this class' `_baseUrl` property
	 * 
	 * @return RestfulRecordRequest 		Returns a reference to `$this->owner` to allow for chaining
	 */
	public function setBaseUrl( $baseUrl ) {
		$this->_baseUrl = $baseUrl;

		return $this->owner;
	}

	/**
	 * getData returns this class' `_data` property
	 * 
	 * @return string 		This class' `_data` property
	 */
	public function getData() {
		return $this->_data;
	}

	/**
	 * setData sets this class' `_data` property
	 * 
	 * @param array $data 				Array to be set as this class' `_data` property
	 * 
	 * @return RestfulRecordRequest 		Returns a reference to `$this->owner` to allow for chaining
	 */
	public function setData( $data ) {
		$this->_data = $data;

		return $this->owner;
	}

	/**
	 * getHeader returns a string representing the header value for the specified key
	 * 
	 * @param string $key 			The header key whose value is to be returned
	 * 
	 * @return string 			String representing the header value for the specified key
	 */
	public function getHeader( $key = "" ) {
		return ArrayHelper::getValue( $this->_headers, $key, false );
	}

	/**
	 * getHeaders returns this class' `_headers` property
	 * 
	 * @return string 		This class' `_headers` property
	 */
	public function getHeaders() {
		return $this->_headers;
	}

	/**
	 * addHeader adds a new header to the headers array
	 * 
	 * @param string $key 				The new header's key
	 * @param string $value 			The new header's value
	 * 
	 * @return RestfulRecordRequest 		Returns a reference to `$this->owner` to allow for chaining
	 */
	public function addHeader( $key = "", $value = "" ) {
		$this->_headers[ $key ] = $value;

		return $this->owner;
	}

	/**
	 * setHeaders sets this class' `_headers` property
	 * 
	 * @param array $headers 			Array to be set as this class' `_headers` property
	 * 
	 * @return RestfulRecordRequest 		Returns a reference to `$this->owner` to allow for chaining
	 */
	public function setHeaders( array $headers = array() ) {
		$this->_headers = $headers;

		return $this->owner;
	}

	/**
	 * getMethod returns this class' `_method` property
	 * 
	 * @return string 		This class' `_method` property
	 */
	public function getMethod() {
		return $this->_method;
	}

	/**
	 * setMethod sets this class' `_method` property
	 * 
	 * @param string $method 			String to be set as this class' `_method` property
	 * 
	 * @return RestfulRecordRequest 		Returns a reference to `$this->owner` to allow for chaining
	 */
	public function setMethod( $method = "" ) {
		$this->_method = $method;

		return $this->owner;
	}

	/**
	 * getParam returns a string representing the param value for the specified key
	 * 
	 * @param string $key 			The param key whose value is to be returned
	 * 
	 * @return string 			String representing the param value for the specified key
	 */
	public function getParam( $key = "" ) {
		return ArrayHelper::getValue( $this->_params, $key, false );
	}

	/**
	 * getParams returns this class' `_params` property
	 * 
	 * @return string 		This class' `_params` property
	 */
	public function getParams() {
		return $this->_params;
	}

	/**
	 * addParam adds a new param to the headers array
	 * 
	 * @param string $key 				The new param's key
	 * @param string $value 			The new param's value
	 * 
	 * @return RestfulRecordRequest 		Returns a reference to `$this->owner` to allow for chaining
	 */
	public function addParam( $key = "", $value = "" ) {
		$this->_params[ $key ] = $value;

		return $this->owner;
	}

	/**
	 * setParams sets this class' `_params` property
	 * 
	 * @param array $params 			Array to be set as this class' `_params` property
	 * 
	 * @return RestfulRecordRequest 		Returns a reference to `$this->owner` to allow for chaining
	 */
	public function setParams( array $params = array() ) {
		$this->_params = $params;

		return $this->owner;
	}

	/**
	 * getPath returns this class' `_path` property
	 * 
	 * @return string 		This class' `_path` property
	 */
	public function getPath() {
		return $this->_path;
	}

	/**
	 * setPath sets this class' `_path` property
	 * 
	 * @param string $path 			Array to be set as this class' `_path` property
	 * 
	 * @return RestfulRecordRequest 		Returns a reference to `$this->owner` to allow for chaining
	 */
	public function setPath( $path = "" ) {
		$this->_path = $path;

		return $this->owner;
	}
}
