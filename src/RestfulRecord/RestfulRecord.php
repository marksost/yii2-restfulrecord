<?php

namespace RestfulRecord;

use RestfulRecord\events\SaveEvent;
use RestfulRecord\exceptions\RestfulRecordException;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * RestfulRecord is a Yii component that provides an ActiveRecord-like interface
 * for interacting with an API (instead of a database).
 * 
 * All basic CRUD operations are supported via several public methods. 
 *
 * @package RestfulRecord
 */
class RestfulRecord extends Component {
	
	// Event constants
	const AFTER_CONSTRUCT = "afterConstruct";
	const AFTER_DELETE = "afterDelete";
	const AFTER_FIND = "afterFind";
	const AFTER_SAVE = "afterSave";
	const BEFORE_DELETE = "beforeDelete";
	const BEFORE_SAVE = "beforeSave";

	/**
	 * @var string ID property used to uniquely identify a model during various RESTful requests
	 */
	protected $id = "";

	/**
	 * @var array  Array of public-facing attributes.
	 * 
	 * Used to provide dynamic properties without having to explicitely define them
	 */
	public $_attributes = array();

	/**
	 * @var array Array of cached models
	 * 
	 * Used within the [[model()]] method to cache instantiations of the same model
	 */
	protected static $_models = array();

	/**
	 * @var RestfulRecordComponent Array of components as defined in the configuration
	 * 
	 * Used within the [[getComponent()]] method to cache instantiations of the same component
	 */
	protected static $_component = array();

	/**
	 * @var boolean Boolean indicating if the current model uses UUIDs as it's primary key
	 */
	protected static $usesUuids = false;

	/**
	 * @var string A string indicating the name of the Yii component as defined in the configuration
	 */
	protected $_componentId = "restfulrecord"; // ID of the component as defined within the configuration file

	/**
	 * @var RestfulRecordUtils Utils object containing various utility functions
	 */
	protected $_utils;

	/**
	 * @var boolean A boolean indicating whether the model is new or not
	 * 
	 * Used when saving a model to detect if a POST or PUT is to be used
	 */
	protected $_new = false;

	/**
	 * @var array Defines an array of class aliases to be supported and used when instantiating a new version of this class
	 */
	public $classAliases = array();

	// Begin PHP magic method overrides
		public function __construct() {
			// All models instantiated with "new" should default to being "new"
			$this->setIsNewResource( true );

			// Set up utils
			$this->setUtils( new RestfulRecordUtils( $this ) );

			// Inherit from component's default attributes
			$this->setAttributes( $this->getComponent()->defaultModelAttributes );

			// Call init function
			$this->init();

			// Attach any defined behaviors
			$this->attachBehaviors( $this->behaviors() );

			// Attach any defined events
			$this->getUtils()->attachEvents( $this->events() );

			// Attach up any class aliases that may need setting up
			// NOTE: Used to re-declare classes with aliases so that variable class names work as expected
			$this->getUtils()->attachClassAliases();

			// Clear any previously-set scope
			$this->clearScope();

			// Trigger after construct event
			$this->trigger( static::AFTER_CONSTRUCT );
		}

		public function __sleep() {
			return array_keys( (array)$this );
		}

		/**
		 * __get is an override of the default get functionality of a PHP class.
		 * It's overriden to allow the checking of attributes as well as class properties.
		 * 
		 * @param string $name 		The property to get
		 * 
		 * @return mixed 			Returns the attribute/property if found
		 */
		public function __get( $name ) {
			// Check attributes first
			if ( $this->hasAttribute( $name ) ) {
				return $this->getAttribute( $name );
			}

			return parent::__get( $name );
		}

		/**
		 * __set is an override of the default set functionality of a PHP class.
		 * It's overriden to allow the setting of attributes instead of class properties.
		 * 
		 * @param string $name 		The property to set
		 * @param mixed $value 		The value of the property to set
		 */
		public function __set( $name = "", $value ) {
			$this->setAttribute( $name, $value );
		}

		/**
		 * __isset is an override of the default isset functionality of a PHP class.
		 * It's overriden to allow the checking of attributes before checking class properties.
		 * 
		 * @param string $name 		The property to check
		 * 
		 * @return boolean 			Returns true if the property is found, false otherwise
		 */
		public function __isset( $name ) {
			// Check attributes first
			return $this->hasAttribute( $name ) ?: parent::__isset( $name );
		}

		/**
		 * __unset is an override of the default unset functionality of a PHP class.
		 * It's overriden to allow the unsetting of attributes over class properties.
		 * 
		 * @param string $name 		The property to unset
		 */
		public function __unset( $name ) {
			// Check attributes first
			if ( $this->hasAttribute( $name ) ){
				$this->unsetAttribute( $name );
			}
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
				"api-responses-behavior" => "RestfulRecord\behaviors\ApiResponsesBehavior",
				"cache-behavior" => "RestfulRecord\behaviors\CacheBehavior",
				"crud-behavior" => "RestfulRecord\behaviors\CrudBehavior",
				"errors-behavior" => "RestfulRecord\behaviors\ErrorsBehavior",
				"resource-behavior" => "RestfulRecord\behaviors\ResourceBehavior",
				"scope-behavior" => "RestfulRecord\behaviors\ScopeBehavior",
			) );
		}

		/**
		 * cast takes a set of attributes and applies them the a new class, returning that new class
		 * This function is useful for compount models that contain sub models (such as curated models)
		 * For instance, the following model attributes may appear on a curated page:
		 * 	array(
		 * 		"id" => 1,
		 * 		"item_id" => 2,
		 * 		"item" => array(
		 * 			...sub model attributes...
		 * 		)
		 * 	)
		 * Using cast:
		 * 	$subModel = $model->cast( "SubModel", $model->item );
		 * 
		 * Will result in a new SubModel with those attributes applied.
		 * The returned model contains all of the functionality of a native RestfulRecord model
		 * NOTE: When not using fully-qualified namespaces, make sure a corresponding class alias is defined in `setClassAliases`
		 * 	This is because `use` statements have no effect on variable-based class instantiation
		 * 
		 * @param string $className 		Name of the class to cast to
		 * @param array $attributes 			Array of attributes to be set on the new casted model
		 * 
		 * @return RestfulRecord (a version of it) 	The newly-casted model
		 */
		public function cast( $className, $attributes = array() ) {
			return ( new $className() )->setAttributes( is_array( $attributes ) ? $attributes : array( $attributes ) );
		}

		/**
		 * compareId is used to compare an ID with the current model's
		 * NOTE: Added to allow for filtering before performing strict checking
		 * NOTE: This is useful for casting IDs to integers before checking for non-UUID models
		 * 
		 * @param mixed $id 			ID to be compared against this model's ID
		 * 
		 * @return boolean 				Returns true if the IDs are the same, false otherwise
		 */
		public function compareId( $id = null ) {
			// Get model ID
			$modelId = $this->getId();

			// Filter IDs if needed
			if ( !static::usesUuid() ) {
				$id = intval( $id );
				$modelId = intval( $modelId );
			}

			// Perform strict comparison
			return $id === $modelId;
		}

		/**
		 * events define event handlers for this class, as well as their handlers
		 * For more information, see http://www.yiiframework.com/doc-2.0/guide-concept-events.html
		 * 
		 * @return array 		An array of zero or more event handlers to attach to this class
		 * 				NOTE: Event handlers should take the form of:
		 * 				array(
		 * 					"unique-handler-key-here" = array(
		 * 						EVENT_NAME_CONSTANT,
		 * 						...mixed... (this param will be passed into the `on` method)
		 * 					),
		 * 				)
		 */
		public function events() {
			return array(
				"after-construct" => array( static::AFTER_CONSTRUCT, array( $this, "afterConstruct" ) ),
				"after-delete" => array( static::AFTER_DELETE, array( $this, "afterDelete" ) ),
				"after-find" => array( static::AFTER_FIND, array( $this, "afterFind" ) ),
				"after-save" => array( static::AFTER_SAVE, array( $this, "afterSave" ) ),
				"before-delete" => array( static::BEFORE_DELETE, array( $this, "beforeDelete" ) ),
				"before-save" => array( static::BEFORE_SAVE, array( $this, "beforeSave" ) ),
			);
		}

		/**
		 * getComponent returns a reference to the Yii component that contains configuration settings for this class to use
		 * Will cache the reference to the component statically for reuse
		 * 
		 * @return Component 		The Yii component that contains configuration settings for this class to use
		 */
		public function getComponent() {
			// Component ID as defined in Yii configuration
			$componentId = $this->getComponentId();

			// Check if the component is already cached, return it if so
			if ( isset( static::$_component[ $componentId ] ) && !is_null( static::$_component[ $componentId ] ) ) {
				return static::$_component[ $componentId ];
			}

			// Check that Yii has knowledge of the component
			static::$_component[ $componentId ] = Yii::$app->has( $componentId ) ? Yii::$app->get( $componentId ) : false;

			if ( static::$_component[ $componentId ] !== false ) {
				return static::$_component[ $componentId ];
			}

			throw new RestfulRecordException( "A valid component is required for RestfulRecord to function properly." );
		}

		/**
		 * load loads a model's attributes based on a passed-in set of data
		 * Useful for populating a model's attributes with POST'd data from the user
		 * NOTE: Optionally can be scoped to certain indexes within the data array
		 * 
		 * @param array $data 		Array of data to be used when populating a model's attributes
		 * @param mixed $formName 	The index within the data array to scope attribute selection to
		 * 					NOTE: When set to null (the default), no scoping will occur
		 * 
		 * @return boolean 			Returns true if the model's attributes were successfully set,
		 * 					false otherwise
		 */
		public function load( array $data, $formName = null ) {
			$data = ArrayHelper::getValue( $data, $formName, $data );

			if ( !empty( $data ) ) {
				$this->setAttributes( $data );

				return true;
			}

			return false;
		}

		/**
		 * model provides a static interface for instantiating a new instance of a model.
		 * It's provided here to keep an ActiveRecord-like interface.
		 * It will also cache new classes to be more efficient.
		 * 
		 * @return RestfulRecord 		An instance of the calling model's class
		 */
		public static function model() {
			// Get calling class' class name
			$className = get_called_class();

			// Check model cache
			if( isset( static::$_models[ $className ] ) ) {
				return static::$_models[ $className ];
			}

			// Create new instance and store in cache
			$model = static::$_models[ $className ] = new $className();
			
			return $model;
		}

		/**
		 * modelFactory abstracts out model stub generation
		 * Returns either a {model}::model() object or a new class for use in various finding operations
		 * Abstracted here to make for easier testing
		 *
		 * @param string $className 		The fully-namespaced class name to instantiate
		 * @param bollean $new 				If true, returns a new model, otherwise returns a stubbed model
		 *
		 * @return RestfulRecord 			The new model stub
		 */
		public function modelFactory( $className = "", $new = false ) {
			return $new === true ? new $className() : $className::model();
		}

		/**
		 * usesUuid returns a boolean value indicating if the current model uses UUIDs
		 * for it's primary key, or integers.
		 * Useful for determining what type of filtering should be done to an ID before making
		 * an API request
		 * 
		 * @return boolean 			Returns true if the model uses UUIDs for it's primary key, false otherwise
		 */
		public static function usesUuid() {
			return static::$usesUuids;
		}
	// End Top-level methods

	// Begin Getters and Setters
		/**
		 * getAttribute returns the value of the attribute as specified by the calling argument.
		 * First checks class properties before checking attributes.
		 * 
		 * @param string $name 			The attribute name to be retrived
		 *
		 * @return mixed 				Returns the value of the attribute if found, otherwise returns false
		 */
		public function getAttribute( $name ) {
			// Check class properties first
			if ( property_exists( $this, $name ) ) {
				return $this->$name;
			}
			
			// Check internal attributes
			if ( isset( $this->_attributes[ $name ] ) ) {
				return $this->_attributes[ $name ];
			}

			return false;
		}

		/**
		 * getAttributes returns one or more attributes defined in this class.
		 * Takes a mixed argument. If it's an array, will be treated as an array of attributes to get
		 * otherwise returns all attributes.
		 * 
		 * @param mixed $attributes 			If an array, will be treated as an array of attribute names
		 * 						otherwise all attributes will be returned
		 * 
		 * @return array 				An array of attributes for this class
		 */
		public function getAttributes( $attributes = true ) {
			// Store reference to all internal attributes
			$modelAttrs = $this->_attributes;

			// Check if argument should be treated as an array of attribute names
			if ( is_array( $attributes ) ) {
				// Reset return array
				$attrs = array();
			
				foreach ( $attributes as $attribute ){
					// Check class properties first
					if ( property_exists( $this, $attribute ) ) {
						$attrs[ $attribute ] = $this->$attribute;
					// Check internal attributes next
					} else {
						$attrs[ $attribute ] = isset( $modelAttrs[ $attribute ] ) ? $modelAttrs[ $attribute ] : null;
					}
				}
			
				return $attrs;
			}

			return $modelAttrs;
		}

		/**
		 * getAttributesToSend returns a filtered array of attributes to send during an API request
		 * Useful for filtering out null or other value types before sending to an API
		 * 
		 * @param array $attributes 			Array of attributes to filter before returning
		 * 
		 * @return array 				Array of filtered attributes
		 */
		public function getAttributesToSend( $attributes ) {
			return $this->getUtils()->filterNullValues( $this->getAttributes( $attributes ) );
		}

		/**
		 * hasAttribute returns a boolean indicating if an attribute is set or not
		 * 
		 * @param string $attribute 			Attribute name to be checked
		 * 
		 * @return boolean 				Returns true if attribute is set, false otherwise
		 */
		public function hasAttribute( $attribute ) {
			return isset( $this->_attributes[ $attribute ] );
		}

		/**
		 * setAttribute takes an attribute name and value and sets it on a class property (if available)
		 * and to the internal attributes array
		 * 
		 * @param string $name 			Name of the attribute to be set
		 * @param mixed $value 			Value of the attribute to be set
		 * 
		 * @return RestfulRecord 			Returns a reference to `$this` to allow for chaining
		 */
		public function setAttribute( $name, $value ) {
			// Check for class property first
			if ( property_exists( $this, $name ) ) {
				$this->$name = $value;
			}

			// Always set to internal attributes
			$this->_attributes[ $name ] = $value;

			return $this;
		}

		/**
		 * setAttributes takes an array of name => values sets them on a class property (if available)
		 * and to the internal attributes array
		 * 
		 * @param array $attributes 			Array of attributes to be set
		 * 
		 * @return RestfulRecord 			Returns a reference to `$this` to allow for chaining
		 */
		public function setAttributes( $attributes = array() ) {
			// Loop through attributes and call setAttribute one at a time
			foreach ( $attributes as $key => $value ) {
				$this->setAttribute( $key, $value );
			}

			return $this;
		}

		/**
		 * unsetAttribute attempts to unset an attribute if it's set
		 * 
		 * @param string $attribute 			Attribute name to be unset
		 * 
		 * @return boolean 				Returns true if attribute is set, false otherwise
		 */
		public function unsetAttribute( $attribute ) {
			if ( $this->hasAttribute( $attribute ) ) {
				unset( $this->_attributes[ $attribute ] );
			}
		}

		/**
		 * getComponentId returns a string representing the key that defines the component in the Yii configuration
		 * NOTE: If you change the key in the configuration, make sure to update it in this class as well
		 * 
		 * @return string 			Key that defines the component in the Yii configuration
		 */
		public function getComponentId() {
			return $this->_componentId;
		}

		/**
		 * setComponentId set the ID string representing the key that defines the component in the Yii configuration
		 * 
		 * @param string $componentId 		String representing the key that defines the component in the Yii configuration
		 * 
		 * @return RestfulRecord 			Returns a reference to `$this` to allow for chaining
		 */
		protected function setComponentId( $componentId = "" ) {
			$this->_componentId = $componentId;

			return $this;
		}

		/**
		 * getId returns an ID based on the configuration setting for what the ID property is
		 * Defaults to "$id"
		 * NOTE: This property must be present for this class to function properly
		 * 
		 * @return integer 		ID of this class as defined by the configuration
		 */
		public function getId() {
			// Get id property name from configuration
			$idProperty = $this->getIdProperty();

			// Check that the property isn't an empty string and that it's set within this class
			if ( !empty( $idProperty ) && isset( $this->$idProperty ) ) {
				return $this->$idProperty;
			}

			throw new RestfulRecordException( "An idProperty must be set and available for RestfulRecord to function properly." );
		}

		/**
		 * getIdProperty returns the id property defined by the configuration
		 * 
		 * @return string 		String representing the id property key as defined by the configuration
		 */
		public function getIdProperty() {
			return $this->restConfig()[ "idProperty" ];
		}

		/**
		 * getUtils returns this class' `_utils` property
		 * 
		 * @return RestfulRecordUtils 			This class' `_utils` property
		 */
		public function getUtils() {
			return $this->_utils;
		}

		/**
		 * setUtils sets this class' `_utils` property
		 * 
		 * @param RestfulRecordUtils 		Class to be set as this class' `_utils` property
		 * 
		 * @return RestfulRecord 			Returns a reference to `$this` to allow for chaining
		 */
		protected function setUtils( RestfulRecordUtils $utils = null ) {
			$this->_utils = $utils;

			return $this;
		}

		/**
		 * isNewResource returns a boolean indicating if this class is considered "new" or not
		 * Used when determining if a save request should be a POST or PUT
		 * 
		 * @return boolean 		Returns true if the model is considered "new" false otherwise
		 */
		public function isNewResource() {
			return $this->_new;
		}

		/**
		 * setIsNewResource sets a boolean indicating if this class is considered "new" or not
		 * Used when determining if a save request should be a POST or PUT
		 * 
		 * @param boolean $new 		Boolean indicating if this class is considered "new" or not
		 * 
		 * @return RestfulRecord 		Returns a reference to `$this` to allow for chaining
		 */
		public function setIsNewResource( $new = true ) {
			$this->_new = $new;

			return $this;
		}
	// End Getters and Setters

	// Begin Before/After methods
		/**
		 * afterConstruct is called as the last thing in the construct method of this class
		 * Useful for performing post-init actions
		 * 
		 * @param Event $event 			The current event being triggered
		 */
		protected function afterConstruct( $event ) {}

		/**
		 * afterFind is called after a model is found
		 * Useful for setting class-level properties based on API-returned attributes
		 * 
		 * @param Event $event 			The current event being triggered
		 */
		protected function afterFind( $event ) {}

		/**
		 * beforeSave is called before sending either a POST or PUT request to the API
		 * Useful for performing validation that determines if the request should be sent
		 * 
		 * @return boolean 			Returns true if the model has no pre-save errors, false otherwise
		 */
		protected function beforeSave() { return !$this->hasErrors(); }

		/**
		 * afterSave is called after a model is saved
		 * Useful for performing cache invalidation and other post-save functionality
		 * 
		 * @param Event $event 			The current event being triggered
		 * 						NOTE: This event will contain a "new" value indicating
		 * 						if this was a [[create]] or [[update]]
		 */
		protected function afterSave( $event ) {}

		/**
		 * beforeDelete is called before sending a DELETE request to the API
		 * Useful for performing validation that determines if the request should be sent
		 * 
		 * @return boolean 			Returns true if the model has no pre-delete errors, false otherwise
		 */
		protected function beforeDelete() { return !$this->hasErrors(); }

		/**
		 * afterDelete is called after a model is deleted
		 * Useful for deleting other relations after a delete occurs
		 * 
		 * @param Event $event 			The current event being triggered
		 */
		protected function afterDelete( $event ) {}
	// End Before/After methods

	// Begin CRUD public API methods
		/**
		 * findAll sends a GET request to the collection endpoint of the API
		 * Will use any $params that are set as query string variables
		 * 
		 * @param array $params 		Array of query string variables to be sent with the request (ex: ?limit=X)
		 * 
		 * @return array 			An array of newly-instantiated models based on API response
		 */
		public function findAll( array $params = array() ) {
			// Make API call to collection endpoint
			$response = $this->get( "collection", $params );

			// Check for successful collection creation
			if ( ( $collection = $this->createCollection( $response ) ) === null ) {
				return array();
			}

			// Set cache if the request is configured to do so
			$this->setResponseInCache( array(
				"collection" => $collection,
			) );

			// Loop through new models, set new-ness and trigger after find event
			foreach ( $collection as $resource ) {
				$resource->setIsNewResource( false );

				$resource->trigger( static::AFTER_FIND );
			}

			return $collection;
		}

		/**
		 * findAllInCache is used to return the result of a GET request to the collection endpoint of the API
		 * only if it is found in cache
		 * 
		 * @param array $params     Array of query string variables to be used when checking the cache (ex: ?limit=X)
		 * 
		 * @return array            If the result is found in cache, returns it as an array of newly-instantiated models
		 *                          Otherwise, returns an empty array
		 */
		public function findAllInCache( array $params = array() ) {
			// Set up cache
			// NOTE: This will override any custom cache configs
			$this->cache( true );

			// Build query info based on this request and configure cache behavior
			$this->configureCache( $this->buildQueryInfo( array(
				"routeType" => "collection",
				"route" => "collection",
				"params" => $params,
				"method" => "GET",
			) ) );

			// Check for response in cache
			// If not found, return an empty array
			// If found, call normal `findAll`, which will internally use the cache
			return $response = $this->getResponseFromCache() === false ? array() : $this->findAll( $params );
		}

		/**
		 * findById sends a GET request to the resource endpoint of the API
		 * Will use any $params that are set as query string variables
		 * 
		 * @param integer $id 			ID of model to request
		 * @param array $params 		Array of query string variables to be sent with the request (ex: ?limit=X)
		 * 
		 * @return RestfulRecord 		If the request is successfull, will return a newly-instantiated model
		 * 					otherwise returns NULL
		 */
		public function findById( $id, array $params = array() ) {
			// Check for required ID
			if ( empty( $id ) ) {
				return null;
			}

			// Set ID property
			$this->{ $this->getIdProperty() } = $id;

			// Make API call to resource endpoint
			$response = $this->get( "resource", $params );

			// Check for successful resource creation
			if ( ( $resource = $this->createResource( $response ) ) === null ) {
				return null;
			}

			// Set cache if the request is configured to do so
			$this->setResponseInCache();

			// Set new-ness and call trigger after find event
			$resource->setIsNewResource( false );

			$resource->trigger( static::AFTER_FIND );

			return $resource;
		}

		/**
		 * findByUuid sends a GET request to the resource endpoint of the API
		 * NOTE: Serves as a proxy for `findById` to make calls more semantically correct
		 */
		public function findByUuid( $uuid, array $params = array() ) {
			// Proxy to `findById`
			return $this->findById( $uuid, $params );
		}

		/**
		 * findOne sends a GET request to the resource endpoint of the API
		 * NOTE: Abstracted here to handle both UUID and non-UUID models
		 * NOTE: Will detect if a model uses UUIDs and call the appropriate method
		 * NOTE: Will also filter the identifier if the model does not use UUIDs
		 */
		public function findOne( $identifier, array $params = array() ) {
			// Check if model uses UUIDs
			$usesUuid = static::usesUuid();

			// Set method
			$method = $usesUuid ? "findByUuid" : "findById";

			// Filter ID if needed
			if ( !$usesUuid ) {
				$identifier = intval( $identifier );
			}

			return $this->$method( $identifier, $params );
		}

		/**
		 * findOneInCache is used to return the result of a GET request to the resource endpoint of the API
		 * only if it is found in cache
		 * 
		 * @param mixed $id         ID of model to request
		 * @param array $params     Array of query string variables to be used when checking the cache (ex: ?limit=X)
		 * 
		 * @return mixed            If the result is found in cache, returns a newly-instantiated model
		 *                          Otherwise, returns null
		 */
		public function findOneInCache( $identifier, array $params = array() ) {
			// Set up cache
			// NOTE: This will override any custom cache configs
			$this->cache( true );

			// Set ID property
			// Filter ID if needed
			$this->{ $this->getIdProperty() } = static::usesUuid() ? $identifier : intval( $identifier );

			// Build query info based on this request and configure cache behavior
			$this->configureCache( $this->buildQueryInfo( array(
				"routeType" => "resource",
				"route" => "resource",
				"params" => $params,
				"method" => "GET",
			) ) );

			// Check for response in cache
			// If not found, return null
			// If found, call normal `findOne`, which will internally use the cache
			return $response = $this->getResponseFromCache() === false ? null : $this->findOne( $identifier, $params );
		}

		/**
		 * updateById sends a PUT request to the resource endpoint of the API
		 * 
		 * NOTE: This function should not be used if you need a reference to this model after saving it
		 * 	It's intended to be a one-off function for updating a model without getting a reference to it back
		 * 	If you need a reference after saving, use a find method, change attribbutes as needed, and call [[save()]]
		 * 	which will call this method internally
		 * Will use any $data as JSON payload data
		 * Will use any $params that are set as query string variables
		 * 
		 * @param integer $id 				ID of model to be updated
		 * @param array $data 			Array of data to be sent in the request body (after JSON encoding)
		 * @param array $params 			Array of query string variables to be sent with the request (ex: ?limit=X)
		 * @param boolean $refresh 			If true, will refresh the model after a successfull save
		 * 
		 * @return boolean 				A boolean indicating if the save was successfull or not
		 */
		public function updateById( $id, array $data = array(), array $params = array(), $refresh = true ) {
			// Check for required ID
			if ( empty( $id ) ) {
				return false;
			}

			// Set ID property
			$this->{ $this->getIdProperty() } = $id;

			// Call beforeSave to do pre-save validation, check for success
			if( $this->beforeSave() ) {
				// Make API call to resource endpoint
				if( ( $response = $this->put( "resource", $data, $params ) ) === null ) {
					return false;
				}

				// Trigger after save event
				$this->trigger( static::AFTER_SAVE );

				// If requested, refresh model to get the latest API data
				if ( $refresh === true ) {
					$this->refresh();
				}

				return true;
			}

			return false;
		}

		/**
		 * updateByUuid sends a PUT request to the resource endpoint of the API
		 * NOTE: Serves as a proxy for `updateById` to make calls more semantically correct
		 */
		public function updateByUuid( $uuid, array $data = array(), array $params = array(), $refresh = true ) {
			// Proxy to `updateById`
			return $this->updateById( $uuid, $data, $params, $refresh );
		}

		/**
		 * updateByUuid sends a PUT request to the resource endpoint of the API
		 * NOTE: Abstracted here to handle both UUID and non-UUID models
		 * NOTE: Will detect if a model uses UUIDs and call the appropriate method
		 * NOTE: Will also filter the identifier if the model does not use UUIDs
		 */
		public function updateOne( $identifier, array $data = array(), array $params = array(), $refresh = true ) {
			// Check if model uses UUIDs
			$usesUuid = static::usesUuid();

			// Set method
			$method = $usesUuid ? "updateByUuid" : "updateById";

			// Filter ID if needed
			if ( !$usesUuid ) {
				$identifier = intval( $identifier );
			}

			return $this->$method( $identifier, $data, $params, $refresh );
		}

		/**
		 * deleteById sends a DELETE request to the resource endpoint of the API
		 * 
		 * NOTE: This function should not be used if you need a reference to this model after deleting it (why would you??)
		 * 	It's intended to be a one-off function for deleting a model without getting a reference to it back
		 * 	If you need a reference after deleting (huh?), use a find method and then call [[destroy()]]
		 * 	which will call this method internally
		 * Will use any $params that are set as query string variables
		 * 
		 * @param integer $id 				ID of model to be updated
		 * @param array $params 			Array of query string variables to be sent with the request (ex: ?limit=X)
		 * 
		 * @return boolean 				A boolean indicating if the delete was successfull or not
		 */
		public function deleteById( $id, array $params = array() ) {
			// Check for required ID
			if ( empty( $id ) ) {
				return false;
			}

			// Set ID property
			$this->{ $this->getIdProperty() } = $id;

			// Call beforeDelete to do pre-delete validation, check for success
			if( $this->beforeDelete() ) {
				// Make API call to resource endpoint
				$response = $this->delete( "resource", $params );

				// Check for deletion errors
				if ( $this->hasErrors() ) {
					return false;
				}

				// Trigger after delete event
				$this->trigger( static::AFTER_DELETE );

				// Unset all attributes
				$this->_attributes = array();

				return true;
			}

			return false;
		}

		/**
		 * deleteByUuid sends a DELETE request to the resource endpoint of the API
		 * NOTE: Serves as a proxy for `deleteById` to make calls more semantically correct
		 */
		public function deleteByUuid( $uuid, array $params = array() ) {
			// Proxy to `deleteById`
			return $this->deleteById( $uuid, $params );
		}

		/**
		 * deleteByUuid sends a DELETE request to the resource endpoint of the API
		 * NOTE: Abstracted here to handle both UUID and non-UUID models
		 * NOTE: Will detect if a model uses UUIDs and call the appropriate method
		 * NOTE: Will also filter the identifier if the model does not use UUIDs
		 */
		public function deleteOne( $identifier, array $params = array() ) {
			// Check if model uses UUIDs
			$usesUuid = static::usesUuid();

			// Set method
			$method = $usesUuid ? "deleteByUuid" : "deleteById";

			// Filter ID if needed
			if ( !$usesUuid ) {
				$identifier = intval( $identifier );
			}

			return $this->$method( $identifier, $params );
		}

		/**
		 * save sends either a POST or PUT request to the API
		 * based on the "new-ness" of a model
		 * 
		 * @param array $data 			Array of data to be sent in the request body (after JSON encoding)
		 * @param boolean $refresh 			If true, will refresh the model after a successfull save
		 * 
		 * @return boolean 				A boolean indicating if the save was successfull or not
		 */
		public function save( array $data = null, $refresh = true ) {
			// Call either create or update internally based on model "new-ness"
			return $this->isNewResource() ? $this->create( $data, $refresh ) : $this->update( $data, $refresh );
		}

		/**
		 * create sends a POST request to the collection endpoint of the API to create a new resource
		 * 
		 * This method is called internally by [[save()]]
		 * 
		 * @param array $data 			Array of data to be sent in the request body (after JSON encoding)
		 * @param boolean $refresh 			If true, will refresh the model after a successfull save
		 * 
		 * @return boolean 				A boolean indicating if the creation was successfull or not
		 */
		public function create( array $data = null, $refresh = true ) {
			// Check for model "new-ness" and pre-save validation success
			if ( $this->isNewResource() && $this->beforeSave() ) {
				// Make API call to collection endpoint
				// NOTE: `getAttributesToSend` is called here to filter variables before sending them
				$response = $this->post( "collection", $this->getAttributesToSend( $data ) );

				// Create new resource to use for resetting this model's properties
				if ( ( $resource = $this->createResource( $response ) ) !== null ) {
					// Set ID property
					$this->{ $this->getIdProperty() } = $resource->getId();

					// Trigger after save event
					$this->trigger( static::AFTER_SAVE, new SaveEvent( array( "new" => true, ) ) );

					// Set "new-ness"
					$this->setIsNewResource( false );

					// If requested, refresh model to get the latest API data
					if ( $refresh === true ) {
						$this->refresh();
					}

					return true;
				}
			}

			return false;
		}

		/**
		 * update sends a PUT request to the resource endpoint of the API to update an existing resource
		 * This method is called internally by [[save()]]
		 * 
		 * @param array $data 			Array of data to be sent in the request body (after JSON encoding)
		 * @param boolean $refresh 			If true, will refresh the model after a successfull update
		 * 
		 * @return boolean 				A boolean indicating if the update was successfull or not
		 */
		public function update( array $data = null, $refresh = true ) {
			// Check for model "new-ness" and pre-save validation success
			if ( !$this->isNewResource() && $this->beforeSave() ){
				// Call `updateOne` internally to update model
				// NOTE: `getAttributesToSend` is called here to filter variables before sending them
				$result = $this->updateOne( $this->getId(), $this->getAttributesToSend( $data ), array(), $refresh );

				return $result;
			}
			
			return false;
		}

		/**
		 * destroy sends a DELETE request to the resource endpoint of the API to delete an existing resource
		 * 
		 * @return boolean 				A boolean indicating if the deletion was successfull or not
		 */
		public function destroy() {
			// Check for model "new-ness" and pre-delete validation success
			if ( !$this->isNewResource() && $this->beforeDelete() ){
				// Call `deleteOne` internally to delete model
				$result = $this->deleteOne( $this->getId() );
				
				return $result;
			}
			
			return false;
		}

		/**
		 * refresh sends a GET request to the resource endpoint of the API to get the latest data
		 * and update this class' internal attributes array
		 * 
		 * @return boolean 				A boolean indicating if the refresh was successfull or not
		 */
		public function refresh() {
			// Check for model "new-ness" and then get a reference to the latest API data
			// by calling `findOne`
			if( !$this->isNewResource() && ( $resource = $this->findOne( $this->getId() ) ) !== null ){
				// Reset this class' internal attributes array
				$this->_attributes = $resource->getAttributes();

				return true;
			}
			
			return false;
		}
	// End CRUD public API methods

	// Begin Utility methods
	// NOTE: These stay here to be easily overriden by various extending classes
		/**
		 * cacheKeys returns an array of macro-based cache keys to be used when forming cache keys
		 * 
		 * @return array     An array of macro-based cache keys
		 */
		public function cacheKeys() {
			return array(
				"resource" => "{endpoint}:{id}",
				"collection" => "{endpoint}",
			);
		}

		/**
		 * getCacheKeyMacros defines a set of macro keys and their model-specific values
		 * to be used when generating cache keys
		 * NOTE: Extending classes should array merge this function's results with their own if needed
		 * 
		 * @param array $options     An array of request-based options
		 *                           NOTE: Useful for extending classes to use for adding request-based macros
		 * 
		 * @return array             An array of macro keys and their model-specific values
		 */
		public function getCacheKeyMacros( array $options = array() ) {
			return array(
				"{id}" => $this->getId(),
				"{endpoint}" => $this->restConfig()[ "endpoint" ],
			);
		}

		/**
		 * getUrlMacros defines a set of macro keys and their model-specific values
		 * to be used when generating a URL to send to an API
		 * NOTE: Extending classes should array merge this function's results with their own if needed
		 * 
		 * @param array $options 		An array of request-based options
		 * 					Useful for extending classes to use for adding request-based macros
		 * 
		 * @return array 			An array of macro keys and their model-specific values
		 */
		public function getUrlMacros( array $options = array() ) {
			return array(
				":id" => $this->getId(),
				":endpoint" => $this->restConfig()[ "endpoint" ],
				":version" => $this->restConfig()[ "version" ],
			);
		}

		/**
		 * restConfig provides a unified function for referencing this class' component's internal configuration
		 * 
		 * @return array 		An array of configuration settings from this class' component
		 */
		public function restConfig() {
			return $this->getComponent()->attributes;
		}

		/**
		 * routes defines a set of macro-based route patterns to be used when sending API requests
		 * 
		 * @return array 		An array of macro-based route patterns
		 */
		public function routes() {
			return array(
				"resource" => "/:version/:endpoint/:id",
				"collection" => "/:version/:endpoint",
			);
		}
	// End Utility methods
}
