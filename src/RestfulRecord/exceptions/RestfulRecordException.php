<?php

namespace RestfulRecord\exceptions;

use yii\base\ErrorException;

/**
 * RestfulRecord is a Yii component that provides an ActiveRecord-like interface
 * for interacting with an API (instead of a database).
 * 
 * RestfulRecordException is a custom exception class to be used by RestfulRecord for all exception throwing.
 * 
 * @package Exceptions
 */
class RestfulRecordException extends ErrorException {}
