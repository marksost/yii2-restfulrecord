<?php

namespace src\RestfulRecord;

use Codeception\Util\Stub;
use RestfulRecord\RestfulRecordCache;
use yii\helpers\Json;

/**
 * @coversDefaultClass RestfulRecord\RestfulRecordCache
 */
class RestfulRecordCacheTest extends \Codeception\TestCase\Test {

	/**
	 * test__BuildKey tests that the buildKey method
	 * returns a properly built key
	 * 
	 * @covers ::buildKey
	 * @dataProvider provider_BuildKey
	 */
	public function test__BuildKey( $input, $encode, $output ) {
		// Mock component
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"encodeKey" => $encode,
		) );
		verify( $cache->buildKey( $input ) )->equals( $output );
	}

	/**
	 * test__Expire tests the expire method
	 * 
	 * @covers ::expire
	 */
	public function test__Expire() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "EXPIRE" );
					verify( $params )->equals( array( "foo", 600, ) );

					return true;
				},
			) ),
		) );

		// Verify return value
		verify( $cache->expire( "foo", 600 ) )->true();
	}

	/**
	 * test__Hdel tests the hdel method
	 * 
	 * @covers ::hdel
	 */
	public function test__Hdel() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "HDEL" );
					verify( $params )->equals( array( "foo", "bar", "baz" ) );

					return false;
				},
			) ),
		) );

		// Verify return value
		verify( $cache->hdel( "foo", array( "bar", "baz" ) ) )->false();
	}

	/**
	 * test__Hexists tests the hexists method
	 * 
	 * @covers ::hexists
	 */
	public function test__Hexists() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "HEXISTS" );
					verify( $params )->equals( array( "foo", "bar" ) );

					return false;

				},
			) ),
		) );

		// Verify return value
		verify( $cache->hexists( "foo", "bar" ) )->false();
	}

	/**
	 * test__Hget tests the hget method
	 * 
	 * @covers ::hget
	 */
	public function test__Hget() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "HGET" );
					verify( $params )->equals( array( "foo", "bar" ) );

					return "test";

				},
			) ),
		) );

		// Verify return value
		verify( $cache->hget( "foo", "bar" ) )->equals( "test" );
	}

	/**
	 * test__Hgetall tests the hgetall method
	 * 
	 * @covers ::hgetall
	 */
	public function test__Hgetall() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "HGETALL" );
					verify( $params )->equals( array( "foo" ) );

					return array( "test" => "one", );

				},
			) ),
		) );

		// Verify return value
		verify( $cache->hgetall( "foo" ) )->equals( array( "test" => "one", ) );
	}

	/**
	 * test__Hkeys tests the hkeys method
	 * 
	 * @covers ::hkeys
	 */
	public function test__Hkeys() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "HKEYS" );
					verify( $params )->equals( array( "foo" ) );

					return array( "test" => "one", );

				},
			) ),
		) );

		// Verify return value
		verify( $cache->hkeys( "foo" ) )->equals( array( "test" => "one", ) );
	}

	/**
	 * test__Hlen tests the hlen method
	 * 
	 * @covers ::hlen
	 */
	public function test__Hlen() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "HLEN" );
					verify( $params )->equals( array( "foo" ) );

					return 1234;
				},
			) ),
		) );

		// Verify return value
		verify( $cache->hlen( "foo" ) )->equals( 1234 );
	}

	/**
	 * test__Hmget tests the hmget method
	 * 
	 * @covers ::hmget
	 */
	public function test__Hmget() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "HMGET" );
					verify( $params )->equals( array( "foo", "bar", "baz" ) );

					return array( "test" => "one", );

				},
			) ),
		) );

		// Verify return value
		verify( $cache->hmget( "foo", array( "bar", "baz" ) ) )->equals( array( "test" => "one", ) );
	}

	/**
	 * test__HmsetChecksHmsetResult tests the hmset method
	 * 
	 * @covers ::hmset
	 */
	public function test__HmsetChecksHmsetResult() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "HMSET" );
					verify( $params )->equals( array( "foo", "bar", "baz", ) );

					return false;
				},
			) ),
		) );

		// Verify return value
		verify( $cache->hmset( "foo", array( "bar", "baz" ) ) )->false();
	}

	/**
	 * test__HmsetChecksExpireResult tests the hmset method
	 * 
	 * @covers ::hmset
	 */
	public function test__HmsetChecksExpireResult() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"expire" => false,
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => true,
			) ),
		) );

		// Verify return value
		verify( $cache->hmset( "foo", array( "bar", "baz" ) ) )->false();
	}

	/**
	 * test__Hmset tests the hmset method
	 * 
	 * @covers ::hmset
	 */
	public function test__Hmset() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					return true;
				},
			) ),
		) );

		// Verify return value
		verify( $cache->hmset( "foo", array( "bar", "baz" ) ) )->true();
	}

	/**
	 * test__Hset tests the hset method
	 * 
	 * @covers ::hset
	 */
	public function test__Hset() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "HSET" );
					verify( $params )->equals( array( "foo", "bar", "baz" ) );

					return false;

				},
			) ),
		) );

		// Verify return value
		verify( $cache->hset( "foo", "bar", "baz" ) )->false();
	}

	/**
	 * test__Hvals tests the hvals method
	 * 
	 * @covers ::hvals
	 */
	public function test__Hvals() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "HVALS" );
					verify( $params )->equals( array( "foo" ) );

					return array( "test" => "one", );
				},
			) ),
		) );

		// Verify return value
		verify( $cache->hvals( "foo" ) )->equals( array( "test" => "one", ) );
	}

	/**
	 * test__Info tests the info method
	 * 
	 * @covers ::info
	 */
	public function test__Info() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "INFO" );

					return "test";
				},
			) ),
		) );

		// Verify return value
		verify( $cache->info() )->equals( "test" );
	}

	/**
	 * test__Lindex tests the lindex method
	 * 
	 * @covers ::lindex
	 */
	public function test__Lindex() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "LINDEX" );
					verify( $params )->equals( array( "foo", 1234 ) );

					return array( "test" => "one", );
				},
			) ),
		) );

		// Verify return value
		verify( $cache->lindex( "foo", 1234 ) )->equals( array( "test" => "one", ) );
	}

	/**
	 * test__Linsert tests the linsert method
	 * 
	 * @covers ::linsert
	 */
	public function test__Linsert() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "LINSERT" );
					verify( $params )->equals( array( "foo", "BEFORE", 1, "bar" ) );

					return 1234;
				},
			) ),
		) );

		// Verify return value
		verify( $cache->linsert( "foo", "BEFORE", 1, "bar" ) )->equals( 1234 );
	}

	/**
	 * test__Llen tests the llen method
	 * 
	 * @covers ::llen
	 */
	public function test__Llen() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "LLEN" );
					verify( $params )->equals( array( "foo" ) );

					return 1234;
				},
			) ),
		) );

		// Verify return value
		verify( $cache->llen( "foo" ) )->equals( 1234 );
	}

	/**
	 * test__Lpop tests the lpop method
	 * 
	 * @covers ::lpop
	 */
	public function test__Lpop() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "LPOP" );
					verify( $params )->equals( array( "foo" ) );

					return array( "test" => "one", );
				},
			) ),
		) );

		// Verify return value
		verify( $cache->lpop( "foo" ) )->equals( array( "test" => "one", ) );
	}

	/**
	 * test__Lpush tests the lpush method
	 * 
	 * @covers ::lpush
	 */
	public function test__Lpush() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "LPUSH" );
					verify( $params )->equals( array( "foo", "bar" ) );

					return 1234;
				},
			) ),
		) );

		// Verify return value
		verify( $cache->lpush( "foo", array( "bar" ) ) )->equals( 1234 );
	}

	/**
	 * test__Lrange tests the lrange method
	 * 
	 * @covers ::lrange
	 */
	public function test__Lrange() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "LRANGE" );
					verify( $params )->equals( array( "foo", 0, -1 ) );

					return array( "test" => "one", );
				},
			) ),
		) );

		// Verify return value
		verify( $cache->lrange( "foo", 0, -1 ) )->equals( array( "test" => "one", ) );
	}

	/**
	 * test__Lrem tests the lrem method
	 * 
	 * @covers ::lrem
	 */
	public function test__Lrem() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "LREM" );
					verify( $params )->equals( array( "foo", 0, "bar" ) );

					return 1234;
				},
			) ),
		) );

		// Verify return value
		verify( $cache->lrem( "foo", 0, "bar" ) )->equals( 1234 );
	}

	/**
	 * test__Lset tests the lset method
	 * 
	 * @covers ::lset
	 */
	public function test__Lset() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "LSET" );
					verify( $params )->equals( array( "foo", 2, "bar" ) );

					return false;
				},
			) ),
		) );

		// Verify return value
		verify( $cache->lset( "foo", 2, "bar" ) )->false();
	}

	/**
	 * test__Ltrim tests the ltrim method
	 * 
	 * @covers ::ltrim
	 */
	public function test__Ltrim() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "LTRIM" );
					verify( $params )->equals( array( "foo", 2, 4 ) );

					return false;
				},
			) ),
		) );

		// Verify return value
		verify( $cache->ltrim( "foo", 2, 4 ) )->false();
	}

	/**
	 * test__Rpop tests the rpop method
	 * 
	 * @covers ::rpop
	 */
	public function test__Rpop() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "RPOP" );
					verify( $params )->equals( array( "foo" ) );

					return array( "test" => "one", );
				},
			) ),
		) );

		// Verify return value
		verify( $cache->rpop( "foo" ) )->equals( array( "test" => "one", ) );
	}

	/**
	 * test__Rpush tests the rpush method
	 * 
	 * @covers ::rpush
	 */
	public function test__Rpush() {
		// Mock cache
		$cache = Stub::make( "RestfulRecord\RestfulRecordCache", array(
			"buildKey" => function( $key ) {
				return $key;
			},
			"redis" => Stub::make( "yii\\redis\Connection", array(
				"executeCommand" => function( $name, $params = array() ) {
					// Verify input
					verify( $name )->equals( "RPUSH" );
					verify( $params )->equals( array( "foo", "bar" ) );

					return 1234;
				},
			) ),
		) );

		// Verify return value
		verify( $cache->rpush( "foo", array( "bar" ) ) )->equals( 1234 );
	}

	/**
	 * 
	 * Begin Data Providers
	 * 
	 */

	/**
	 * provider_BuildKey provides different sets of data to test various cases within
	 * the buildKey method
	 */
	public function provider_BuildKey() {
		return array(
			// Encode (string input)
			array(
				"input" => "foo",
				"encode" => true,
				"output" => md5( "foo" ),
			),
			// No encode (string input)
			array(
				"input" => "foo",
				"encode" => false,
				"output" => "foo",
			),
			// Encode (non-string input)
			array(
				"input" => array( "foo" => "bar", ),
				"encode" => true,
				"output" => md5( Json::encode( array( "foo" => "bar", ) ) ),
			),
			// No encode (non-string input)
			array(
				"input" => array( "foo" => "bar", ),
				"encode" => false,
				"output" => Json::encode( array( "foo" => "bar", ) ),
			),
		);
	}
}
