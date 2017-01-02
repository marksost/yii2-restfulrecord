<?php

namespace app\controllers;

use ViewSetRenderer\Controller;
use Yii;

/**
 * Controller used for all actions starting with "/site"
 * (or any actions routed here through custom URL rules)
 */
class SiteController extends Controller {

	public function actions() {
		return array(
			// Provide default error action
			'error' => array(
				'class' => 'yii\web\ErrorAction',
			),
		);
	}

	public function actionIndex() {
		return $this->render( 'index' );
	}
}
