<?php

use yii\helpers\Html;

/**
 * Main view layout file for the application.
 * Provides core HTML structure and injects view data into it
 * 
 * For more information on overriding core layout files, see
 * http://www.yiiframework.com/doc-2.0/guide-structure-views.html#using-layouts
 */
?>
<?php $this->beginPage(); ?>
	<!DOCTYPE html>
	<html class="no-js" lang="<?php echo Yii::$app->language; ?>">
		<head>
			<title><?php echo Html::encode( Yii::$app->name ); ?></title>

			<?php $this->head(); ?>
		</head>
		<body>
			<?php $this->beginBody() ?>
				<div class="site-content">
					<?php echo $content; ?>
				</div>
			<?php $this->endBody(); ?>
		</body>
	</html>
<?php $this->endPage(); ?>
