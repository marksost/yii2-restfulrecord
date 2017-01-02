<?php
	use yii\helpers\Html;
	use Yii;

	echo nl2br( Html::encode( $message ) ), "<pre>";
	var_dump( Yii::$app->getRequest() );
?>
