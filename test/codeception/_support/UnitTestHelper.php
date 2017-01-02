<?php

namespace Codeception\Module;

use Yii;

class UnitTestHelper extends \Codeception\Module {
	/**
	 * setUserRole abstracts setting user roles to make this file more DRY
	 * Also resets Yii's user component identity class after setting the new user role
	 * 
	 * @param string $role 			Role of the user to be set
	 * @param UserModel $userModel 		The user model identity class to apply the role to
	 */
	public function setUserRole( $role = null, $userModel = null ) {
		// Reflect private method
		$setRole = $this->reflectMethod( "app\models\auth\UserModel", "setRole" );

		$setRole->invoke( $userModel, $role );

		// Reset user's identity class
		Yii::$app->user->setIdentity( $userModel );
	}

	/**
	 * reflectClass abstracts class reflection to make tests more DRY
	 *
	 * @param string $className 			Class name to be reflected
	 *
	 * @return ReflectionClass 				Reflected class
	 */
	public function reflectClass( $className ) {
		$class = new \ReflectionClass( $className );

		return $class;
	}

	/**
	 * reflectMethod abstracts protected/private method reflection to make tests more DRY
	 * 
	 * @param string $className 			Class name to be reflected
	 * @param string $methodName 			Method name of be reflected
	 * 
	 * @return ReflectionMethod 				Reflected method
	 */
	public function reflectMethod( $className, $methodName ) {
		$method = new \ReflectionMethod( $className, $methodName );
		
		$method->setAccessible( true );

		return $method;
	}

	/**
	 * reflectProperty abstracts protected/private property reflection to make tests more DRY
	 *
	 * @param string $className 			Class name to be reflected
	 * @param string $property 				Property name of be reflected
	 *
	 * @return ReflectionProperty 				Reflected property
	 */
	public function reflectProperty( $className, $property ) {
		// Reflect class
		$class = $this->reflectClass( $className );

		// Get property from class
		$prop = $class->getProperty( $property );

		$prop->setAccessible( true );

		return $prop;
	}

	/**
	 * invokeMethod abstracts protected/private method invocation to make tests more DRY
	 *
	 * @param string $class 				Class to invoke the method on
	 * @param string $methodName 			The name of the method to be invoked
	 * @param array  $params 				An array of arguments to be passed along to the invoked method
	 * 
	 * @return InvokedMethod 				Invoked method	 
	 */
	public function invokeMethod( $class, $methodName, array $params = array() ) {
		// Reflect method
		$method = $this->reflectMethod( get_class( $class ), $methodName );

		return $method->invokeArgs( $class, $params );
	}

	/**
	 * setProperty abstracts protected/private property setting to make tests more DRY
	 *
	 * @param string $class 				Class to set property on
	 * @param string $property 				Property name of be reflected
	 * @param string $value 				The value to which $property will be set
	 *
	 * @return ReflectionProperty 				Reflected property with new value
	 */
	public function setProperty( $class, $property, $value ) {
		// Reflect class
		$reflectedClass = $this->reflectClass( get_class( $class ) );

		// Get property
		$property = $reflectedClass->getProperty( $property );

		// Set as accessible
		$property->setAccessible( true );

		// Set value
		$property->setValue( $class, $value );

		return $property;
	}
}
