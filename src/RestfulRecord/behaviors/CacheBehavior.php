<?php

namespace RestfulRecord\behaviors;

use RestfulRecord\RestfulRecord;
use yii\base\Behavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use Yii;

/**
 * CacheBehavior adds caching functionality to RestfulRecord
 * 
 * @package Behaviors
 */
class CacheBehavior extends Behavior {

	/**
	 * @var string A string indicating the component ID of the cache component as defined by the application
	 */
	public $cacheComponentId = "cache";

	/**
	 * @var string A string indicating the delimiter to be used between cache key parts
	 */
	public $cacheKeyDelimiter = ":";

	/**
	 * @var string A string indicating the symbol to use for global master key lists
	 */
	public $globalKeyIndicator = "*";

	/**
	 * @var Cache The defined cache component as set by the application
	 */
	protected $_cacheComponent = null;

	/**
	 * @var array An array indicating the current request's cache configuration
	 * 
	 * NOTE: If an empty array, indicates caching should be disallowed
	 */
	protected $_cacheConfig = array();

	/**
	 * @var integer The default duration (in seconds) to be used when no duration is provided
	 */
	protected $_defaultDuration = 300; // 5 mins

	/**
	 * @var boolean Indicates if a cached value was found
	 * 
	 * NOTE: Used to determine if a response should be stored in cache after successfull resource creation
	 */
	protected $_foundInCache = false;

	/**
	 * @var integer The factor by which master key TTLs should outlive their newest stored key
	 * 
	 * Ex: If the master key list has a key appended to it with a TTL of 5 minutes,
	 *     and this property is 4, the master key list's TTL will be 20 minutes
	 */
	protected $_masterKeyTTLFactor = 1;

	/**
	 * @var array An array indicating the previous request's cache configuration
	 * 
	 * NOTE: Provided as a way to reference a previous cache operation without needed to prevent
	 * cleanup operations post-cache setting
	 */
	protected $_previousCacheConfig = array();

	/**
	 * @var array An array indicating the current request's options to be used to make a request to an API
	 * 
	 * NOTE: If an empty array, indicates cahing should be disallowed
	 */
	protected $_requestOptions = array();

	/**
	 * init function that sets up various class properties and configuration
	 */
	public function init() {
		// Call parent version of this method
		parent::init();

		// Set cache component
		if ( Yii::$app->has( $this->cacheComponentId ) ) {
			$this->setCacheComponent( Yii::$app->get( $this->cacheComponentId ) );
		}
	}

	// Begin Top-level methods
		/**
		 * addKeyToMasterKeyList is used to add a single key to this behavior's owner's
		 * master key list
		 * NOTE: Useful for associating relational responses with their parent, such as
		 * content lists for a given section with that section. This can aid in more comprehensive
		 * cache invalidation
		 * 
		 * @param string $key     The key to be added to this behavior's owner's master key list
		 * 
		 * @return boolean        Returns the result of storing the key
		 */
		public function addKeyToMasterKeyList( $key ) {
			// Treat this as a single key addition to a resource's master key list
			// NOTE: Checks for empty keys inline
			return empty( $key ) ? false : $this->handleResourceMasterKeyUpdate( array( "key" => $key, ) );
		}

		/**
		 * cache is a method to be called inline with a finder method (findAll, findOne, etc)
		 * It is used to set up a base cache configuration to be used when getting/setting
		 * caches after a find operation takes place
		 * 
		 * NOTE: This method accepts various types of arguments which indicate different "can cache" states
		 * NOTE: The previous argument format of this method, a string and an integer is NO LONGER SUPPORTED
		 *       Using that previous format will HAVE NO EFFECT AND NO CACHING WILL TAKE PLACE
		 * 
		 * @param mixed $config     An argument indicating different "can cache" states
		 *                          - If the argument is a boolean, it indicates that cache should/shouldn't occur
		 *                            NOTE: Cache configuration will use the "auto" mode, which means this behavior will attempt
		 *                            to format and store caches based on the request as best it can
		 *                          - If the argument is a string, it indicates the previous generation of cache calls
		 *                            NOTE: This method is NOT SUPPORTED but is provided for non-breaking backwards compatibility
		 *                          - If the argument is an array, it indicates a custom cache configuration that should be merged
		 *                            with the "auto" configuration
		 *                            NOTE: Useful for providing various option overrides
		 * 
		 * @return RestfulRecord    Returns a reference to `$this->owner` to allow for chaining
		 */
		public function cache( $config = true ) {
			// Reset state no matter the argument type
			$this->resetState();

			switch( gettype( $config ) ) {
				// NOTE: Strings indicate the previous generation of cache calls
				//       Using the previous format will HAVE NO EFFECT AND NO CACHING WILL TAKE PLACE
				case "string":
					return $this->owner;
				case "boolean":
					// A value of false indicates caching should be disallowed
					if ( $config === false ) {
						return $this->owner;
					}

					// A value of true indicates "auto" mode
					$this->setCacheConfig( $this->getDefaultCacheConfig() );
					break;
				case "array":
					// Merge config with default
					$config = ArrayHelper::merge( $this->getDefaultCacheConfig(), $config );

					// Set custom cache config
					$this->setCacheConfig( $config );
					break;
			}

			return $this->owner;
		}

		/**
		 * configureCache is used to set an internal request option configuration, which is used by the
		 * `shouldUseCache` method when determining if the cache should be used
		 * 
		 * @param array $options     An array of request options to be used to make a request to an API
		 * 
		 * @return RestfulRecord     Returns a reference to `$this->owner` to allow for chaining
		 */
		public function configureCache( array $options = array() ) {
			// Check for empty cache config (shouldn't proceed)
			if ( empty( $this->getCacheConfig() ) ) {
				return $this->owner;
			}

			// Set internal request options array
			$this->setRequestOptions( $options );

			// Generate cache key and reset it
			$this->_cacheConfig[ "key" ] = $this->generateCacheKey();

			// Reset type
			$this->_cacheConfig[ "type" ] = $this->formActualType();

			return $this->owner;
		}

		/**
		 * getResponseFromCache attempts to return a value found in cache based on the current request's
		 * cache configuration settings
		 * 
		 * @return mixed     If the cached value is found, will return it
		 *                   Otherwise returns false
		 */
		public function getResponseFromCache() {
			// Check for a valid cache component
			if ( ( $cacheComponent = $this->getCacheComponent() ) === null ) {
				return false;
			}

			// Get cache configuration
			$cacheConfig = $this->getCacheConfig();

			// Get key
			$key = ArrayHelper::getValue( $cacheConfig, "key", "invalid" );

			// Check if the current key exists in cache and attempt to retrieve it
			if ( $cacheComponent->exists( $key ) === false || ( $data = $cacheComponent->hget( $key, "data" ) ) === null ) {
				return false;
			}

			// Set found in cache status
			$this->setWasFoundInCache( true );

			// Return data
			return $data;
		}

		/**
		 * setResponseInCache attempts to set a value in cache based on the current request's
		 * cache configuration settings
		 * 
		 * @param array $data     An array of data representing responses to be set in cache
		 *                        Supported values:
		 *                        - response (array)   The raw response from the current request
		 *                        - collection (array) For collection types, an array of models
		 *                                             created from the API response
		 * 
		 * @return boolean     Returns the result of the cache set operation
		 */
		public function setResponseInCache( array $data = array() ) {
			// Check if cache should be used or if value was found in cache
			if ( !$this->shouldUseCache() || $this->wasFoundInCache() ) {
				// Reset state
				$this->resetState();

				return false;				
			}

			// Get cache configuration
			$cacheConfig = $this->getCacheConfig();

			// Get API response
			$response = $this->owner->getApiResponse();

			// Form data array with default value fallbacks
			$data = ArrayHelper::merge( array(
				"response" => $response && method_exists( $response, "getBody" ) ? $response->getBody()->__toString() : "",
				"collection" => array(),
			), $data );

			// Get key, type, and duration
			$key = ArrayHelper::getValue( $cacheConfig, "key", false );
			$type = ArrayHelper::getValue( $cacheConfig, "type", false );
			$duration = ArrayHelper::getValue( $cacheConfig, "duration", $this->_defaultDuration );
			$cacheEmptyResponses = ArrayHelper::getValue( $cacheConfig, "cacheEmptyResponses", false );

			// Check for valid input
			// NOTE: key and type are required
			// NOTE: Response is required
			// NOTE: For "collection" type stores, one or more collection objects is required if cacheEmptyResponses is false
			if ( $key === false || $type === false ||
				empty( $data[ "response" ] ) ||
				( !$cacheEmptyResponses && $type === "collection" && empty( $data[ "collection" ] ) )
			) {
				return false;
			}

			// Form fields to be stored based on type
			$fields = $this->{ $type === "collection" ? "formCollectionStore" : "formResourceStore" }( $data );

			// Flatten fields for redis insertion
			$flatFields = $this->flattenArray( $fields );

			// Get storage result
			$result = $this->getCacheComponent()->hmset( $key, $flatFields, $duration );

			// Update master key lists when storage succeeds
			if ( $result ) {
				$this->updateMasterKeyLists( ArrayHelper::merge( $cacheConfig, array(
					"fields" => $fields,
					"data" => $data,
				) ) );
			}

			// Reset state
			$this->resetState();

			// Return result
			return $result;
		}

		/**
		 * shouldUseCache is used to check if requests should use the cache
		 * NOTE: This method checks internal properties, which should be set before calling this method
		 * 
		 * @return boolean     Returns true if various criteria are met to indicate cache
		 *                     should be used for getting/setting responses
		 */
		public function shouldUseCache() {
			// Get cache configuration
			$cacheConfig = $this->getCacheConfig();

			// Get request options
			$options = $this->getRequestOptions();

			// Store values from options
			$method = ArrayHelper::getValue( $options, "method", "invalid" );

			// Check for valid cache component
			// Check for valid cache config
			// Check for valid cache key
			// Check for valid request options
			// Check for valid method
			return !empty( $this->getCacheComponent() ) &&
				!empty( $cacheConfig ) &&
				ArrayHelper::getValue( $cacheConfig, "key", false ) !== false &&
				ArrayHelper::getValue( $cacheConfig, "type", false ) !== false &&
				!empty( $options ) &&
				strtoupper( $method ) === "GET";
		}
	// End Top-level methods

	// Begin Getters and Setters
		/**
		 * getCacheComponent returns this class' `_cacheComponent` property
		 * 
		 * @return Cache     This class' `_cacheComponent` property
		 */
		public function getCacheComponent() {
			return $this->_cacheComponent;
		}

		/**
		 * setCacheComponent sets this class' `_cacheComponent` property
		 * 
		 * @param Cache $component     Value to be set as this class' `_cacheComponent` property
		 * 
		 * @return CacheBehavior       Returns a reference to `$this` to allow for chaining
		 */
		protected function setCacheComponent( $component = null ) {
			$this->_cacheComponent = $component;

			return $this;
		}

		/**
		 * getCacheConfig returns this class' `_cacheConfig` property
		 * 
		 * @return mixed     This class' `_cacheConfig` property
		 */
		public function getCacheConfig() {
			return $this->_cacheConfig;
		}

		/**
		 * setCacheConfig sets this class' `_cacheConfig` property
		 * 
		 * @param mixed $config     Value to be set as this class' `_cacheConfig` property
		 *                          NOTE: An empty array will indicate an unset cache configuration, thus caching will be disallowed
		 * 
		 * @return CacheBehavior    Returns a reference to `$this` to allow for chaining
		 */
		protected function setCacheConfig( array $config = array() ) {
			$this->_cacheConfig = $config;

			return $this;
		}

		/**
		 * getPreviousCacheConfig returns this class' `_previousCacheConfig` property
		 * 
		 * @return mixed     This class' `_previousCacheConfig` property
		 */
		public function getPreviousCacheConfig() {
			return $this->_previousCacheConfig;
		}

		/**
		 * setPreviousCacheConfig sets this class' `_previousCacheConfig` property
		 * 
		 * @param mixed $config     Value to be set as this class' `_previousCacheConfig` property
		 * 
		 * @return CacheBehavior    Returns a reference to `$this` to allow for chaining
		 */
		protected function setPreviousCacheConfig( array $config = array() ) {
			$this->_previousCacheConfig = $config;

			return $this;
		}

		/**
		 * getRequestOptions returns this class' `_requestOptions` property
		 * 
		 * @return mixed     This class' `_requestOptions` property
		 */
		public function getRequestOptions() {
			return $this->_requestOptions;
		}

		/**
		 * setRequestOptions sets this class' `_requestOptions` property
		 * 
		 * @param mixed $options     Value to be set as this class' `_requestOptions` property
		 *                           NOTE: An empty array will indicate unset request options, thus caching will be disallowed
		 * 
		 * @return CacheBehavior     Returns a reference to `$this` to allow for chaining
		 */
		protected function setRequestOptions( array $options = array() ) {
			$this->_requestOptions = $options;

			return $this;
		}

		/**
		 * wasFoundInCache returns this class' `_foundInCache` property
		 * 
		 * @return boolean     This class' `_foundInCache` property
		 */
		public function wasFoundInCache() {
			return $this->_foundInCache;
		}

		/**
		 * setWasFoundInCache sets this class' `_foundInCache` property
		 * 
		 * @param boolean $foundInCache     Value to be set as this class' `_foundInCache` property
		 * 
		 * @return CacheBehavior            Returns a reference to `$this` to allow for chaining
		 */
		protected function setWasFoundInCache( $foundInCache = false ) {
			$this->_foundInCache = $foundInCache;

			return $this;
		}
	// End Getters and Setters

	// Begin Utility methods
		/**
		 * filterKeyArrayPart is used to remove blacklisted parts before generating a data-aware cache key
		 * 
		 * @param array $data               An array of data to filter
		 * @param string $restConfigKey     The rest config key to use to get blacklisted data
		 * 
		 * @return array                    A filtered request parameter array
		 */
		protected function filterKeyArrayPart( array $data = array(), $restConfigKey = "" ) {
			// Get blacklisted data
			$blacklisted = ArrayHelper::getValue( $this->owner->restConfig(), $restConfigKey, array() );

			// Loop blacklisted data, removing any from key data
			foreach ( $blacklisted as $key ) {
				ArrayHelper::remove( $data, $key );
			}

			return $data;
		}

		/**
		 * flattenArray is used to "flatten" an associative array
		 * E.x an array like:
		 *      array(
		 *           "foo" => "bar",
		 *           "test" => 1234,
		 *      )
		 * 
		 * Would become:
		 *      array(
		 *           "foo",
		 *           "bar",
		 *           "test",
		 *           1234,
		 *      )
		 * 
		 * NOTE: This is used primarily for hash insertion into redis
		 * NOTE: Supported values for this function currently only include strings and integers
		 * 
		 * @param array $array     Array to be flattened
		 * 
		 * @return array           The flattened array
		 */
		protected function flattenArray( array $array = array() ) {
			// Set default return array
			$temp = array();

			foreach ( $array as $key => $value ) {
				// Validate value
				if ( !is_string( $value ) && !is_integer( $value ) ) {
					return array();
				}

				// Insert key and value into array
				$temp[] = $key;
				$temp[] = $value;
			}

			return $temp;
		}

		/**
		 * formActualType is used to form the actual type of a cache configuration
		 * It is used when the type is set to "auto" and also verifies that a type
		 * has a corresponding entry in the current model's cache keys array
		 * 
		 * @return mixed     Returns a string indicating a valid actual type if found
		 *                   Otherwise returns false
		 */
		protected function formActualType() {
			// Get cache configuration
			$cacheConfig = $this->getCacheConfig();

			// Get type from config
			$type = ArrayHelper::getValue( $cacheConfig, "type", "auto" );

			// If type is auto, use route type from request options
			$type = $type !== "auto" ? $type : $this->getTypeFromRequestOptions();

			// Validate type and return it or false
			return $type !== false && ArrayHelper::keyExists( $type, $this->owner->cacheKeys() ) ? $type : false;
		}

		/**
		 * formAwareKeyPart is used to form an "aware" part of a cache key. It takes the form of:
		 *      :{aware-type}:{json-encoded-data}
		 * 
		 * @param string $type     The type of "aware" part to be formed and returned
		 * @param array $data      The data to be JSON-encoded
		 * 
		 * @return string          The formed key part
		 */
		protected function formAwareKeyPart( $type = "", array $data = array() ) {
			return empty( $data ) ? "" : $this->cacheKeyDelimiter.$type.$this->cacheKeyDelimiter.Json::encode( $data );
		}

		/**
		 * formCollectionResourceKeys is used to generate cache keys for a collection of resources
		 * taken from a collection response
		 * These keys are stored along side the collection response to allow for easier invalidation of related keys
		 * 
		 * @param array $collection     An array of zero or more resources to generate cache keys for
		 * 
		 * @return array                An array of zero or more generated cache keys
		 */
		protected function formCollectionResourceKeys( array $collection = array() ) {
			// Set default return array
			$keys = array();

			// Loop through resources, generating a cache key for each
			foreach ( $collection as $model ) {
				// Check that model is valid
				if ( $model instanceOf RestfulRecord === false ) {
					continue;
				}

				// Use cache method to generate default cache config
				// Use configCache method to generate cache key, hard-coding type as "resource"
				$model->cache()->configureCache( array( "routeType" => "resource" ) );

				// Add generated key to return array
				if ( ( $key = ArrayHelper::getValue( $model->getCacheConfig(), "key", false ) ) !== false ) {
					$keys[] = $key;
				}
			}

			return $keys;
		}

		/**
		 * formCollectionStore is used to take a collection template and fill it in with actual
		 * data to be used when storing collection objects in cache
		 * 
		 * @param array $data     An array of data representing responses to be set in cache
		 * 
		 * @return array          An array of data to be used when storing collection objects in cache
		 */
		protected function formCollectionStore( array $data = array() ) {
			// Form resource keys
			$keys = $this->formCollectionResourceKeys( ArrayHelper::getValue( $data, "collection", array() ) );

			return ArrayHelper::merge( $this->getCollectionTemplate(), array(
				"keys" => Json::encode( $keys ),
				"data" => ArrayHelper::getValue( $data, "response", "" ),
			) );
		}

		/**
		 * formResourceStore is used to take a resource template and fill it in with actual
		 * data to be used when storing resource objects in cache
		 * 
		 * @param array $data     An array of data representing responses to be set in cache
		 * 
		 * @return array          An array of data to be used when storing resource objects in cache
		 */
		protected function formResourceStore( array $data = array() ) {
			return ArrayHelper::merge( $this->getResourceTemplate(), array(
				"data" => ArrayHelper::getValue( $data, "response", "" ),
			) );
		}

		/**
		 * generateCacheKey is used to generate a cache key for a given request
		 * NOTE: Will check for a custom key and return it if found
		 * NOTE: Will append param and header key parts for keys designnated as "aware" for either
		 * 
		 * @return string     A formed cache key
		 */
		protected function generateCacheKey() {
			// Get cache configuration
			$cacheConfig = $this->getCacheConfig();

			// Get request options
			$options = $this->getRequestOptions();

			// Check for custom key
			if( ArrayHelper::getValue( $cacheConfig, "key", false ) !== false ) {
				return $cacheConfig[ "key" ];
			}

			// Get and validate type
			if ( ( $type = $this->formActualType() ) === false ) {
				return false;
			}

			// Replace cache key macros
			$key = $this->owner->getUtils()->replaceMacros( $this->owner->cacheKeys()[ $type ], $options, "getCacheKeyMacros" );

			// Get params and headers
			$params = ArrayHelper::getValue( $options, "params", array() );
			$headers = ArrayHelper::getValue( $options, "headers", array() );

			// Check if key should be param-aware and there are params
			// NOTE: Will filter out any blacklisted param as set by the model being cached
			if ( ArrayHelper::getValue( $cacheConfig, "paramAware", true ) && !empty( $params ) ) {
				$key .= $this->formAwareKeyPart( "params", $this->filterKeyArrayPart( $params, "blacklistedCacheParams" ) );
			}

			// Check if key should be header-aware and there are headers
			// NOTE: Will filter out any blacklisted header as set by the model being cached
			if ( ArrayHelper::getValue( $cacheConfig, "headerAware", false ) && !empty( $headers ) ) {
				$key .= $this->formAwareKeyPart( "headers", $this->filterKeyArrayPart( $headers, "blacklistedCacheHeaders" ) );
			}

			return $key;
		}

		/**
		 * getCollectionResourceIds is used to return an array of IDs for resources contained
		 * within a collection
		 * 
		 * @param array $collection     The collection to get resource IDs from
		 * 
		 * @return array                An array of resource IDs gotten from a collection
		 */
		protected function getCollectionResourceIds( array $collection = array() ) {
			// Set default return array
			$ids = array();

			// Loop through resources
			foreach ( $collection as $model ) {
				// Check that model is valid
				if ( $model instanceOf RestfulRecord === false ) {
					continue;
				}

				// Add ID to return array
				$ids[] = $model->getId();
			}

			return $ids;
		}

		/**
		 * getCollectionTemplate returns an array containing the default state for a collection
		 * hash to use when storing collections in cache
		 * 
		 * @return array     An array of default collection hash values
		 *                   Supported values:
		 *                   - type (string) The type of hash this is (should always be "collection")
		 *                   - keys (array)  An array of keys that represent each resource stored in the collection
		 *                                   NOTE: This array is used to invalidate resources contained in the collection
		 *                                   when the collection itself needs to be invalidated
		 *                   - data (string) A JSON-encoded string containing the response from the API to be cached
		 *                                   NOTE: If set to fault when attempting to store a cache, will indicate the hash should not be stored
		 *                                   NOTE: Even though Redis supports deep hashing, it is more efficient to
		 *                                   store JSON-encoded data as Redis will optimize smaller hashes better, and
		 *                                   the use case here is to return the full response, not do hash field lookups
		 *                                   For more information on this, see:
		 *                                   http://redis.io/topics/memory-optimization
		 *                                   http://stackoverflow.com/questions/16375188/redis-strings-vs-redis-hashes-to-represent-json-efficiency
		 */
		protected function getCollectionTemplate() {
			return array(
				"type" => "collection",
				"keys" => array(),
				"data" => false,
			);
		}

		/**
		 * getDefaultCacheConfig returns a default set of configuration to be used by various methods
		 * when manipulating cache values
		 * 
		 * @return array     An array of default cache configuration values
		 *                   Supported values:
		 *                   - type (string)         The type of cache hash to store
		 *                                           NOTE: A default of "auto" indicates this behavior will attempt to figure out the best
		 *                                           hash type to use
		 *                                           NOTE: Accepted values are "resource", "collection", and "auto"
		 *                                           NOTE: It may be useful to set this manually so the proper hash type is used
		 *                                           Example: Getting only one resource but needing to use a findAll to filter
		 *                                           by non-ID fields
		 *                   - key (mixed)           The cache key to use when storing/retrieving a value
		 *                                           NOTE: If set to `false`, will auto-generate a cache key based on model settings
		 *                   - duration (integer)    The amount of time (in seconds) to cache a value
		 *                   - paramAware (boolean)  If cache keys should be made "param aware"
		 *                                           Means request params will be used when forming cache keys
		 *                   - headerAware (boolean) If cache keys should be made "header aware"
		 *                                           Means request headers will be used when forming cache keys
		 *                   - globalKey (boolean)   If the cache key should be considered a "global" key
		 *                                           NOTE: This means the key will be stored in the "global" keys array within
		 *                                           the master key list
		 */
		protected function getDefaultCacheConfig() {
			return array(
				"type" => "auto",
				"key" => false,
				"duration" => ( int )ArrayHelper::getValue( $this->owner->restConfig(), "cacheDuration", $this->_defaultDuration ),
				"paramAware" => true,
				"headerAware" => false,
				"globalKey" => false,
				"cacheEmptyResponses" => false,
			);
		}

		/**
		 * getMasterKeyClassName is used to return a properly-formed class name
		 * for use with master key lists
		 * 
		 * @return string     A properly-formed class name with back-slashes replaced with delimiters
		 */
		protected function getMasterKeyClassName() {
			// Get class name from owner
			$className = get_class( $this->owner );

			// Return class name with back-slashes replaced with a delimiter
			return str_replace( "\\", $this->cacheKeyDelimiter, $className );
		}

		/**
		 * getResourceTemplate returns an array containing the default state for a resource
		 * hash to use when storing resources in cache
		 * 
		 * @return array     An array of default resource hash values
		 *                   Supported values:
		 *                   - type (string) The type of hash this is (should always be "resource")
		 *                   - data (string) A JSON-encoded string containing the response from the API to be cached
		 *                                   NOTE: If set to fault when attempting to store a cache, will indicate the hash should not be stored
		 *                                   NOTE: Even though Redis supports deep hashing, it is more efficient to
		 *                                   store JSON-encoded data as Redis will optimize smaller hashes better, and
		 *                                   the use case here is to return the full response, not do hash field lookups
		 *                                   For more information on this, see:
		 *                                   http://redis.io/topics/memory-optimization
		 *                                   http://stackoverflow.com/questions/16375188/redis-strings-vs-redis-hashes-to-represent-json-efficiency
		 */
		protected function getResourceTemplate() {
			return array(
				"type" => "resource",
				"data" => false,
			);
		}

		/**
		 * getTypeFromRequestOptions is used to return the routeType value from a set of request options
		 * 
		 * @return mixed     If routeType exists within the request options array, returns it's value
		 *                   Otherwise returns false
		 */
		protected function getTypeFromRequestOptions() {
			// Get and return route type from options
			return ArrayHelper::getValue( $this->getRequestOptions(), "routeType", false );
		}

		/**
		 * resetState is used to reset any properties in this behavior pertaining to a cache-able state
		 * NOTE: This method should be called after every set of the cache to allow for multiple cache calls
		 * in a method chain or reuse of a model
		 * 
		 * @return CacheBehavior     Returns a reference to `$this` to allow for chaining
		 */
		protected function resetState() {
			// Store current cache config before resetting it
			$this->setPreviousCacheConfig( $this->getCacheConfig() );

			// Reset cache config
			$this->setCacheConfig( array() );

			// Reset request options
			$this->setRequestOptions( array() );

			// Reset found in cache status
			$this->setWasFoundInCache( false );
			
			return $this;
		}

		/**
		 * updateMasterKeyLists is used to update master key lists
		 * These key lists are used for invalidation of Redis keys by the cache invalidation service
		 * 
		 * @param array $data     An array of data to be used when generating keys to store in the master key list
		 * 
		 * @return boolean        Returns the result of storing the keys
		 */
		protected function updateMasterKeyLists( array $data = array() ) {
			// Get type and global key settings
			$type = ArrayHelper::getValue( $data, "type", false );
			$globalKey = ArrayHelper::getValue( $data, "globalKey", false );

			// Check for a valid type
			if ( $type === false ) {
				return false;
			}

			// Handle global keys
			if ( $globalKey ) {
				return $this->handleGlobalMasterKeyUpdate( $data );
			}

			// Handle different types
			if ( $type === "collection" ) {
				return $this->handleCollectionMasterKeyUpdate( $data );
			} else if ( $type === "resource" ) {
				return $this->handleResourceMasterKeyUpdate( $data );
			}

			// Catch-all return value
			return false;
		}

		/**
		 * handleGlobalMasterKeyUpdate is used to update a global master key list for a given class
		 * 
		 * @param array $data     An array of data to be used when generating keys to store in the master key list
		 * 
		 * @return boolean        Returns the result of storing the key
		 */
		protected function handleGlobalMasterKeyUpdate( array $data = array() ) {
			// Form master list key
			$masterListKey = $this->getMasterKeyClassName().$this->cacheKeyDelimiter.$this->globalKeyIndicator;

			// Get key and duration to store
			$key = ArrayHelper::getValue( $data, "key", false );
			$duration = ArrayHelper::getValue( $data, "duration", $this->_defaultDuration );

			// Append key to master list
			return $this->rpushToMasterKey( $masterListKey, $key, $duration );
		}

		/**
		 * handleCollectionMasterKeyUpdate is used to update a set of master key lists
		 * based on the resources contained within a collection
		 * 
		 * @param array $data     An array of data to be used when generating keys to store in the master key list
		 * 
		 * @return boolean        Returns the result of storing the keys
		 */
		protected function handleCollectionMasterKeyUpdate( array $data = array() ) {
			// Form master list key base
			$masterListKeyBase = $this->getMasterKeyClassName().$this->cacheKeyDelimiter;

			// Get key and duration to store
			$key = ArrayHelper::getValue( $data, "key", false );
			$duration = ArrayHelper::getValue( $data, "duration", $this->_defaultDuration );

			// Get collection resource IDs
			$ids = $this->getCollectionResourceIds( ArrayHelper::getValue( $data, array( "data", "collection" ) , array() ) );

			// Loop throuh ids, adding key to master key list
			foreach ( $ids as $id ) {
				// Form ID-specific master list key and append key to it
				$this->rpushToMasterKey( $masterListKeyBase.$id, $key, $duration );
			}

			return true;
		}

		/**
		 * handleResourceMasterKeyUpdate is used to update a resource's master key list
		 * 
		 * @param array $data     An array of data to be used when generating keys to store in the master key list
		 * 
		 * @return boolean        Returns the result of storing the keys
		 */
		protected function handleResourceMasterKeyUpdate( array $data = array() ) {
			// Form master list key
			$masterListKey = $this->getMasterKeyClassName().$this->cacheKeyDelimiter.$this->owner->getId();

			// Get key and duration to store
			$key = ArrayHelper::getValue( $data, "key", false );
			$duration = ArrayHelper::getValue( $data, "duration", $this->_defaultDuration );

			// Append key to master list
			return $this->rpushToMasterKey( $masterListKey, $key, $duration );
		}

		/**
		 * rpushToMasterKey uses Redis' `rpush` method to append a key to a
		 * master key list
		 * NOTE: Abstracted here to make master key list appending more DRY
		 * 
		 * @param string $masterKey    The master key list to append the key to
		 * @param string $key          The key to append to the master key list
		 * @param integer $ttl         The time-to-live to use when setting the TTL for the master key list
		 *                             NOTE: This provides incremental-backoff functionality 
		 *                             to ensure master key lists are present X-times as long
		 *                             as the newest stored key's TTL while still ensuring they eventually expire
		 * 
		 * @return boolean             Returns the result of storing the key
		 */
		protected function rpushToMasterKey( $masterKey, $key, $ttl = 0 ) {
			// Check for a valid cache component
			if ( ( $cacheComponent = $this->getCacheComponent() ) === null ) {
				return false;
			}

			// Append key to master list
			$cacheComponent->rpush( $masterKey, array( $key ) );

			// NOTE: A TTL of 0 will automatically unset a master key
			if ( $ttl > 0 ) {
				// Modify TTL by a defined factor and alter the master key's TTL based on input
				$cacheComponent->expire( $masterKey, $ttl * $this->_masterKeyTTLFactor );
			}

			return true;
		}
	// End Utility methods
}
