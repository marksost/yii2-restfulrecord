<?php

namespace RestfulRecord\behaviors;

use yii\base\Behavior;

/**
 * ScopeBehavior adds scope functionality to RestfulRecord
 *
 * Scopes can be used to store an internal "state" for a model,
 * which can be called inline during CRUD operations. This allows
 * various before/after methods to use the current scope in order
 * to determine if certain actions should occur.
 *
 * For instance, on listing pages, a piece of content may only need
 * certain relations loaded, as compared to on it's actual display
 * page. The `afterFind` method can use scope to determine which relations
 * should be loaded when the model is called like:
 *
 * ContentModel::model()->setScope( "listing" )->findOne( "foo" );
 *
 * where the `afterFind` method can check:
 *
 * if ( $this->getScope() === "listing" ) {
 *      // Do listing relation lookups
 * }
 *
 * NOTE: It is recommended that various scopes should be implemented
 * in this class as constants, making checking more consistent.
 * Constants should take the form of: `SCOPE_FOO`
 *
 * @package Behaviors
 */
class ScopeBehavior extends Behavior {

	/**
	 * @const string String representing the "listing" scope.
	 */
	const SCOPE_LISTING = "listing";

	/**
	 * @var string String containing the current scope for this behavior's owner
	 */
	protected $_scope = "";

	/**
	 * clearScope resets this class' `_scope` property to an empty string
	 *
	 * @return RestfulRecord     Returns a reference to `$this->owner` to allow for chaining
	 */
	public function clearScope() {
		return $this->setScope( "" );
	}

	/**
	 * getScope returns this class' `_scope` property
	 *
	 * @return string     This class' `_scope` property
	 */
	public function getScope() {
		return $this->_scope;
	}

	/**
	 * hasScope returns a boolean indicating if the specified input matches the current scope
	 *
	 * @param string $scope     The string to be checked against the current scope
	 *
	 * @return boolean          If the string matches the current scope, returns true
	 *                          Otherwise returns false
	 */
	public function hasScope( $toCheck = "" ) {
		return $toCheck === $this->getScope();
	}

	/**
	 * resetScope resets this class' `_scope` property to the specified input
	 *
	 * @param string $scope      String to be set as this class' `_scope` property
	 *
	 * @return RestfulRecord     Returns a reference to `$this->owner` to allow for chaining
	 */
	public function resetScope( $scope = "" ) {
		return $this->setScope( $scope );
	}

	/**
	 * setScope sets this class' `_scope` property
	 *
	 * @param string $scope      String to be set as this class' `_scope` property
	 *
	 * @return RestfulRecord     Returns a reference to `$this->owner` to allow for chaining
	 */
	public function setScope( $scope = "" ) {
		$this->_scope = $scope;

		return $this->owner;
	}
}
