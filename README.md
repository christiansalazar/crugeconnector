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
<?php
// SAVE AS yourapp/facebook-callback.php, review your config main settings, must match.

// SELECT URL MODE.

// USE THIS WHEN URL MANAGER IS ACTIVATED, must match your URL rules: 
// DONT FORGET "?" at the end.
// $url = "index.php/site/crugeconnector/crugekey/facebook/crugemode/callback?";

// USE THIS WHEN YOU ARE NOT USING URL MANAGER:
// 
$url = "index.php?r=/site/crugeconnector&crugekey=facebook&crugemode=callback";

// common code:
foreach($_GET as $key=>$val)
	$url .= "&".$key."=".urlencode($val);
header("Location: ".$url);
~~~

