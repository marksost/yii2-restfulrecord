<?php

namespace RestfulRecord;

use yii\helpers\Json;
use yii\redis\Cache;

/**
 * RestfulRecordCache extends Yii's Redis extension (http://www.yiiframework.com/doc-2.0/ext-redis-index.html)
 * in order to provide methods not supported directly by the extension. Namely, hash commands.
 * 
 * RestfulRecord is a Yii component that provides an ActiveRecord-like interface
 * for interacting with an API (instead of a database).
 * 
 * @package RestfulRecord
 */
class RestfulRecordCache extends Cache {

	/**
	 * @var boolean If true, keys will be encoded using md5, otherwise they'll be left as plaintext
	 */
	public $encodeKey = true;

	/**
	 * buildKey is used to build a normalized cache key from input
	 * NOTE: Overridden here to ensure consistent behavior that may be relied upon
	 * within other service (such as cach invalidation)
	 * 
	 * @param mixed $key     The key to be built against
	 * 
	 * @return string        The built key
	 */
	public function buildKey( $key ) {
		// Ensures the input was a string, otherwise JSON-encodes it
		$key = is_string( $key ) ? $key : Json::encode( $key );

		// Encodes the key if a class property is set
		$key = $this->encodeKey === true ? md5( $key ) : $key;

		// Return prefixed key
		return $this->keyPrefix.$key;
	}

	/**
	 * expire is used to call an EXPIRE command on Redis
	 * For more information on EXPIRE, see http://redis.io/commands/expire
	 * 
	 * @param string $key           The key to set an expiration time on
	 * @param integer $expires      The amount of time (in seconds) for the key to live in cache
	 * @param boolean $buildKey     Indicates if this method should "build" a key or not
	 *                              NOTE: Provided so methods that call this method internally
	 *                              and pass in already-build keys can skip a double-encoding
	 * 
	 * @return boolean              Returns the result of the operation from redis
	 */
	public function expire( $key, $expires = 300, $buildKey = true ) {
		// Build key if needed
		$key = $buildKey ? $this->buildKey( $key ) : $key;

		return ( bool )$this->redis->executeCommand( "EXPIRE", array( $key, $expires ) );
	}

	/**
	 * hdel is used to call an HDEL command on Redis
	 * For more information on HDEL, see http://redis.io/commands/hdel
	 * 
	 * @param string $key       The key to be used when deleting the hash fields
	 * @param array $fields     An array of hash field keys to be deleted
	 *                          NOTE: The array should be flat, and take the form of:
	 *                          array( "key", "key-two", ...etc... )
	 * 
	 * @return boolean          Returns the result of the operation from redis
	 */
	public function hdel( $key, $fields = array() ) {
		// Build key
		$key = $this->buildKey( $key );

		// Add key to fields array
		array_unshift( $fields, $key );

		return ( bool )$this->redis->executeCommand( "HDEL", $fields );
	}
	
	/**
	 * hexists is used to call an HEXISTS command on Redis
	 * For more information on HEXISTS, see http://redis.io/commands/hexists
	 * 
	 * @param string $key       The key to be used when checking for a hash field
	 * @param string $field     The field to be checked for
	 * 
	 * @return boolean          Returns the result of the operation from redis
	 */
	public function hexists( $key, $field ) {
		// Build key
		$key = $this->buildKey( $key );

		return ( bool )$this->redis->executeCommand( "HEXISTS", array( $key, $field ) );
	}
	
	/**
	 * hget is used to call an HGET command on Redis
	 * For more information on HGET, see http://redis.io/commands/hget
	 * 
	 * @param string $key       The key to be used when getting the value of a hash field
	 * @param string $field     The field for which a value should be returned
	 * 
	 * @return mixed            Returns the result of the operation from redis
	 */
	public function hget( $key, $field ) {
		// Build key
		$key = $this->buildKey( $key );

		return $this->redis->executeCommand( "HGET", array( $key, $field ) );
	}
	
	/**
	 * hgetall is used to call an HGETALL command on Redis
	 * For more information on HGETALL, see http://redis.io/commands/hgetall
	 * 
	 * @param string $key     The key to be used when getting all field keys and values
	 * 
	 * @return array          Returns the result of the operation from redis
	 */
	public function hgetall( $key ) {
		// Build key
		$key = $this->buildKey( $key );

		return $this->redis->executeCommand( "HGETALL", array( $key ) );
	}
	
	/**
	 * hkeys is used to call an HKEYS command on Redis
	 * For more information on HKEYS, see http://redis.io/commands/hkeys
	 * 
	 * @param string $key     The key to be used when getting all field keys
	 * 
	 * @return array          Returns the result of the operation from redis
	 */
	public function hkeys( $key ) {
		// Build key
		$key = $this->buildKey( $key );

		return $this->redis->executeCommand( "HKEYS", array( $key ) );
	}
	
	/**
	 * hlen is used to call an HLEN command on Redis
	 * For more information on HLEN, see http://redis.io/commands/hlen
	 * 
	 * @param string $key     The key to be used when getting field length
	 * 
	 * @return integer        Returns the result of the operation from redis
	 */
	public function hlen( $key ) {
		// Build key
		$key = $this->buildKey( $key );

		return $this->redis->executeCommand( "HLEN", array( $key ) );
	}
	
	/**
	 * hmget is used to call an HMGET command on Redis
	 * For more information on HMGET, see http://redis.io/commands/hmget
	 * 
	 * @param string $key       The key to be used when getting the hash field values
	 * @param array $fields     An array of hash field keys to get the values of
	 *                          NOTE: The array should be flat, and take the form of:
	 *                          array( "key", "key-two", ...etc... )
	 * 
	 * @return array            Returns the result of the operation from redis
	 */
	public function hmget( $key, $fields = array() ) {
		// Build key
		$key = $this->buildKey( $key );

		// Add key to fields array
		array_unshift( $fields, $key );

		return $this->redis->executeCommand( "HMGET", $fields );
	}

	/**
	 * hmset is used to call an HMSET command on Redis
	 * For more information on HMSET, see http://redis.io/commands/hmset
	 * 
	 * @param string $key          The key to be used when setting the hash
	 * @param array $data          An array of hash fields and values
	 *                             NOTE: The array should be flat, and take the form of:
	 *                             array( "key" => "value", "key-two" => "value-two", ...etc... )
	 * @param integer $expires     The amount of time (in seconds) for the hash to live in cache
	 * 
	 * @return boolean             Returns true if both the value and expiration time are set
	 *                             Otherwise returns false
	 */
	public function hmset( $key, array $data = array(), $expires = 300 ) {
		// Build key
		$key = $this->buildKey( $key );

		// Add key to data array
		array_unshift( $data, $key );

		// Check for success
		if ( ( bool )$this->redis->executeCommand( "HMSET", $data ) === false ) {
			return false;
		}

		// Set expiration value
		if ( $this->expire( $key, $expires, false ) === false ) {
			return false;
		}

		return true;
	}

	/**
	 * hset is used to call an HSET command on Redis
	 * For more information on HSET, see http://redis.io/commands/hset
	 * 
	 * @param string $key       The key to be used when setting a hash field's value
	 * @param string $field     The field whose value is to be set
	 * @param mixed $value      The value to be set
	 * 
	 * @return boolean          Returns the result of the operation from redis
	 */
	public function hset( $key, $field, $value ) {
		// Build key
		$key = $this->buildKey( $key );

		return ( bool )$this->redis->executeCommand( "HSET", array( $key, $field, $value ) );
	}
	
	/**
	 * hvals is used to call an HVALS command on Redis
	 * For more information on HVALS, see http://redis.io/commands/hvals
	 * 
	 * @param string $key     The key to be used when getting all field values
	 * 
	 * @return array          Returns the result of the operation from redis
	 */
	public function hvals( $key ) {
		// Build key
		$key = $this->buildKey( $key );

		return $this->redis->executeCommand( "HVALS", array( $key ) );
	}

	/**
	 * info is used to call an INFO command on Redis
	 * For more information on INFO, see http://redis.io/commands/info
	 * 
	 * @return array          Returns the result of the operation from redis
	 */
	public function info() {
		return $this->redis->executeCommand( "INFO" );
	}

	/**
	 * lindex is used to call an LINDEX command on Redis
	 * For more information on LINDEX, see http://redis.io/commands/lindex
	 * 
	 * @param string $key        The key to be used when getting an element by it's index
	 * @param integer $index     The index of the element to return
	 * 
	 * @return mixed             Returns the result of the operation from redis
	 */
	public function lindex( $key, $index ) {
		// Build key
		$key = $this->buildKey( $key );

		return $this->redis->executeCommand( "LINDEX", array( $key, $index ) );
	}

	/**
	 * linsert is used to call an LINSERT command on Redis
	 * For more information on LINSERT, see http://redis.io/commands/linsert
	 * 
	 * @param string $key          The key to be used when inserting a new value into a list
	 * @param string $position     Whether to insert the value before or after the pivot point
	 *                             NOTE: The value of this should either be "BEFORE" or "AFTER"
	 * @param mixed $pivot         The value to insert the new value before or after
	 * @param mixed $value         The value to insert
	 * 
	 * @return integer             Returns the result of the operation from redis
	 */
	public function linsert( $key, $position, $pivot, $value ) {
		// Build key
		$key = $this->buildKey( $key );

		return $this->redis->executeCommand( "LINSERT", array( $key, $position, $pivot, $value ) );
	}

	/**
	 * llen is used to call an LLEN command on Redis
	 * For more information on LLEN, see http://redis.io/commands/llen
	 * 
	 * @param string $key     The key to be used when getting the length of a list
	 * 
	 * @return integer        Returns the result of the operation from redis
	 */
	public function llen( $key ) {
		// Build key
		$key = $this->buildKey( $key );

		return $this->redis->executeCommand( "LLEN", array( $key ) );
	}

	/**
	 * lpop is used to call an LPOP command on Redis
	 * For more information on LPOP, see http://redis.io/commands/lpop
	 * 
	 * @param string $key     The key to be used when removing and returning the first element of a list
	 * 
	 * @return mixed          Returns the result of the operation from redis
	 */
	public function lpop( $key ) {
		// Build key
		$key = $this->buildKey( $key );

		return $this->redis->executeCommand( "LPOP", array( $key ) );
	}

	/**
	 * lpush is used to call an LPUSH command on Redis
	 * For more information on LPUSH, see http://redis.io/commands/lpush
	 * 
	 * @param string $key       The key to be used when inserting values at the head of a list
	 * @param array $values     An array of one or more values to be added to the head of a list
	 * 
	 * @return integer          Returns the result of the operation from redis
	 */
	public function lpush( $key, array $values = array() ) {
		// Build key
		$key = $this->buildKey( $key );

		// Add key to values array
		array_unshift( $values, $key );

		return $this->redis->executeCommand( "LPUSH", $values );
	}

	/**
	 * lrange is used to call an LRANGE command on Redis
	 * For more information on LRANGE, see http://redis.io/commands/lrange
	 * 
	 * @param string $key        The key to be used when getting a range of values from a list
	 * @param integer $start     The starting position for getting a range of values from a list
	 * @param integer $stop      The ending position for getting a range of values from a list
	 * 
	 * @return array             Returns the result of the operation from redis
	 */
	public function lrange( $key, $start, $stop ) {
		// Build key
		$key = $this->buildKey( $key );

		return $this->redis->executeCommand( "LRANGE", array( $key, $start, $stop ) );
	}

	/**
	 * lrem is used to call an LREM command on Redis
	 * For more information on LREM, see http://redis.io/commands/lrem
	 * 
	 * @param string $key        The key to be used when removing values from a list
	 * @param integer $count     The amount of occurances of a value to remove
	 * @param mixed $value       The value to remove
	 * 
	 * @return integer           Returns the result of the operation from redis
	 */
	public function lrem( $key, $count, $value ) {
		// Build key
		$key = $this->buildKey( $key );

		return $this->redis->executeCommand( "LREM", array( $key, $count, $value ) );
	}

	/**
	 * lset is used to call an LSET command on Redis
	 * For more information on LSET, see http://redis.io/commands/lset
	 * 
	 * @param string $key        The key to be used when setting a value at a specific index within a list
	 * @param integer $index     The index at which to set a value
	 * @param mixed $value       The value to be set
	 * 
	 * @return boolean           Returns the result of the operation from redis
	 */
	public function lset( $key, $index, $value ) {
		// Build key
		$key = $this->buildKey( $key );

		return ( bool )$this->redis->executeCommand( "LSET", array( $key, $index, $value ) );
	}

	/**
	 * ltrim is used to call an LTRIM command on Redis
	 * For more information on LTRIM, see http://redis.io/commands/ltrim
	 * 
	 * @param string $key        The key to be used when trimming a list
	 * @param integer $start     The starting position for trimming a list
	 * @param integer $stop      The ending position for trimming a list
	 * 
	 * @return boolean           Returns the result of the operation from redis
	 */
	public function ltrim( $key, $start, $stop ) {
		// Build key
		$key = $this->buildKey( $key );

		return ( bool )$this->redis->executeCommand( "LTRIM", array( $key, $start, $stop ) );
	}

	/**
	 * rpop is used to call an RPOP command on Redis
	 * For more information on RPOP, see http://redis.io/commands/rpop
	 * 
	 * @param string $key     The key to be used when removing and returning the last element of a list
	 * 
	 * @return mixed          Returns the result of the operation from redis
	 */
	public function rpop( $key ) {
		// Build key
		$key = $this->buildKey( $key );

		return $this->redis->executeCommand( "RPOP", array( $key ) );		
	}

	/**
	 * rpush is used to call an RPUSH command on Redis
	 * For more information on RPUSH, see http://redis.io/commands/rpush
	 * 
	 * @param string $key       The key to be used when inserting values at the tail of a list
	 * @param array $values     An array of one or more values to be added to the tail of a list
	 * 
	 * @return integer          Returns the result of the operation from redis
	 */
	public function rpush( $key, array $values = array() ) {
		// Build key
		$key = $this->buildKey( $key );

		// Add key to values array
		array_unshift( $values, $key );

		return $this->redis->executeCommand( "RPUSH", $values );
	}
}
