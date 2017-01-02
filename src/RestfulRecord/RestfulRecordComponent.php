<?php

namespace RestfulRecord;

use Yii;
use yii\base\Component;

/**
 * RestfulRecordComponent is the component that get's defined within the Yii configuration to provide
 * server-based configuration options.
 * 
 * RestfulRecord will use this class to interface with and use those configuration values.
 * 
 * RestfulRecord is a Yii component that provides an ActiveRecord-like interface
 * for interacting with an API (instead of a database).
 * 
 * @package RestfulRecord
 */
class RestfulRecordComponent extends Component {
	
	/**
	 * @var array Array of publicly-defined configuration options
	 */
	public $config = array();

	/**
	 * @var array Array of default model attributes
	 * 
	 * All new models will have these attributes set during class construction
	 */
	public $defaultModelAttributes = array();

	/**
	 * @var array Configuration defaults
	 * 
	 * NOTE: these are overridden via Yii configuration files
	 */
	public $attributes = array(
		"acceptType" => "application/json",
		"allowNullValues" => false,
		"apiKey" => "",
		"apiSecret" => "",
		"apiUrl" => "",
		"apiUrls" => "",
		"blacklistedCacheParams" => array(),
		"blacklistedCacheHeaders" => array(),
		"blacklistedParams" => array(),
		"cacheDuration" => 300,
		"enableLogging" => false,
		"errorKey" => "errors",
		"idProperty" => "id",
		"itemsContainerKey" => "data",
		"requestOptions" => array(),
		"timeout" => 30,
		"version" => "",
	);

	/**
	 * init function that sets up various class properties and configuration
	 */
	public function init() {
		// Override default configuration settings with those defined in Yii config files
		foreach ( $this->config as $key => $value ){
			$this->attributes[ $key ] = $value;
		}

		// Get array of available API URLs
		$this->attributes[ "apiUrls" ] = $this->parseApiUrls();

		// Select API URL based on round-robin
		$this->attributes[ "apiUrl" ] = $this->selectApiUrl( $this->attributes[ "apiUrls" ] );
	}

	// Begin Utility methods
		/**
		 * parseApiUrls takes a comma-separated string of API URLs
		 * and breaks them down into individual API URLs
		 * 
		 * @return array 		Array of individual API URLs
		 */
		protected function parseApiUrls() {
			// Set up empty return value
			$urls = array();

			// Store reference to string of comma-separated API URLs
			$apiUrls = $this->attributes[ "apiUrls" ];

			// Check string length
			if ( !empty( $apiUrls ) ) {
				// Break API URLs into array
				$apiUrls = explode( ",", $apiUrls );

				foreach ( $apiUrls as $url ) {
					// Remove leading and trailing spaces
					// As well as trailing slashes
					$url = rtrim( trim( $url ), "/" );

					// Check string length
					if ( empty( $url ) ) {
						continue;
					}

					// Check for protocol
					// NOTE: Guzzle doesn't like protocol-less URLs, so avoid them...
					if ( !preg_match( "/^(?:https|http)?\:?\/\//", $url ) ) {
						$url = "http://".$url;
					}

					$urls[] = $url;
				}
			}

			return $urls;
		}

		/**
		 * selectApiUrl shuffles around the available API urls and returns a semi-random one
		 * to provide a round-robin-like API URL selection
		 * 
		 * @param array $urls 			Array of one or more API URLs
		 * 
		 * @return string 			A single API URL
		 */
		protected function selectApiUrl( array $urls = array() ) {
			// Check for valid input
			if ( empty( $urls ) ) {
				return "";
			}

			// Shuffle API URLs to be able to return a semi-random one
			shuffle( $urls );

			return $urls[ 0 ];
		}
	// End Utility methods
}
