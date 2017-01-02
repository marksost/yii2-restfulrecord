<?php

namespace RestfulRecord\events;

use yii\base\Event;

/**
 * SaveEvent is an extension of a normal Yii event
 * 
 * It provides additional model-state functionality, like indicating
 * whether a model is new or not
 * 
 * @package Events
 */
class SaveEvent extends Event {

	/**
	 * @var boolean An indicator if the model was new or not
	 */
	public $new = false;
}
