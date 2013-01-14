CrugeConnector
==============

Remote Login using Google, Facebook and others (extensible) for Yii Framework.

**author:**

Christian Salazar H. <christiansalazarh@gmail.com>

**licence:**

[http://opensource.org/licenses/bsd-license.php](http://opensource.org/licenses/bsd-license.php "http://opensource.org/licenses/bsd-license.php")

**full wiki, documentation & installation:**

[http://yiiframeworkenespanol.org/wiki/index.php?title=CrugeConnector](http://yiiframeworkenespanol.org/wiki/index.php?title=CrugeConnector "http://yiiframeworkenespanol.org/wiki/index.php?title=CrugeConnector")

(Source Code & Comments in English)

**Screen Shot:**

![CrugeConnector Screen Capture](https://bitbucket.org/christiansalazarh/crugeconnector/downloads/crugeconnector--viewlogin.png "CrugeConnector Screen Capture")

**Quick Setup:**

1- in protected/config/main.php, under 'imports' key:
~~~
'application.extensions.crugeconnector.*',
~~~
2- in protected/config/main.php, under 'components' key:
~~~
	'crugeconnector'=>array(
	'class'=>'ext.crugeconnector.CrugeConnector',
		'hostcontrollername'=>'site',
		'onSuccess'=>array('site/loginsuccess'),
		'onError'=>array('site/loginerror'),
		'clients'=>array(
			'facebook'=>array(
				// required by crugeconnector:
				'enabled'=>true,
				'class'=>'ext.crugeconnector.clients.Facebook',
				'callback'=>'http://yoursite.com/app/facebook-callback.php',
				// required by remote interface:
				'client_id'=>"yourappid",
				'client_secret'=>"yoursecretid",
				'scope'=>'email, read_stream',
			),	
			'google'=>array(
				// required by crugeconnector:
				'enabled'=>true,
				'class'=>'ext.crugeconnector.clients.Google',
				'callback'=>'http://yoursite.com/app1/google-callback.php',
				// required by remote interface:
				'hostname'=>'yoursite.com',
				'identity'=>'https://www.google.com/accounts/o8/id',
				'scope'=>array('contact/email'),
			),
			'tester'=>array(
				// required by crugeconnector:
				'enabled'=>false,
				'class'=>'ext.crugeconnector.clients.Tester',
				// required by remote interface:
			),
		),
	),
~~~
3- in protected/controllers/siteContoller.php (by default)
~~~
	public function actions()
	{
		return array(
			'captcha'=>array(
				'class'=>'CCaptchaAction',
				'backColor'=>0xFFFFFF,
			),
			'page'=>array(
				'class'=>'CViewAction',
			),
			// ADD THIS:
			'crugeconnector'=>array('class'=>'CrugeConnectorAction'),
		);
	}

	// and this actions:

	public function actionLoginSuccess($key){

		$loginData = Yii::app()->crugeconnector->getStoredData();
		// loginData: remote user information in JSON format.

		$info = $loginData;
		$this->renderText('<h1>Welcome!</h1><p>'.$info.'</p> key='.$key);
	}

	public function actionLoginError($key, $message=''){
		$this->renderText('<h1>Login Error</h1><p>'.$message.'</p> key='.$key);
	}
~~~
4- insert the component into your protected/views/site/login view:
~~~
	<?php if(Yii::app()->crugeconnector->hasEnabledClients){ ?>
	<div class='crugeconnector'>
		<span>Use your Facebook or Google account:</span>
		<ul>
		<?php 
			$cc = Yii::app()->crugeconnector;
			foreach($cc->enabledClients as $key=>$config){
				$image = CHtml::image($cc->getClientDefaultImage($key));
				echo "<li>".CHtml::link($image,
					$cc->getClientLoginUrl($key))."</li>";
			}
		?>
		</ul>
	</div>
	<?php } ?>
~~~
5- create a callback (one for google and another one for facebook)
~~~
	the first one for facebook: (dont forget to create a facebook app)
	
	<?php
	// copy this code into /yourapp/facebook-callback.php
	// don't forget to stablish the $yii path !!
	//
	$yii=dirname(__FILE__).'/../yii/framework/yii.php';
	$config=dirname(__FILE__).'/protected/config/main.php';
	
	defined('YII_DEBUG') or define('YII_DEBUG',false);
	defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);
	
	$_GET['r'] = '/site/crugeconnector';	// <--using 'site' ?
	$_GET['crugekey'] = 'facebook';			// <--facebook key
	$_GET['crugemode'] = 'callback';
	
	require_once($yii);
	Yii::createWebApplication($config)->run();
	?>

	and another one for google: 

	<?php
	// copy this code into /yourapp/google-callback.php
	// don't forget to stablish the $yii path !!
	//
	$yii=dirname(__FILE__).'/../yii/framework/yii.php';
	$config=dirname(__FILE__).'/protected/config/main.php';
	
	defined('YII_DEBUG') or define('YII_DEBUG',false);
	defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);
	
	$_GET['r'] = '/site/crugeconnector';	// <--using 'site' ?
	$_GET['crugekey'] = 'google';			// <--google key
	$_GET['crugemode'] = 'callback';
	
	require_once($yii);
	Yii::createWebApplication($config)->run();
	?>

~~~

