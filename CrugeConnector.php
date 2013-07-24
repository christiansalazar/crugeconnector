<?php
/**
 * CrugeConnector 
 *
 * A reusable component to provide login on a remote source like Google Facebook
 * or similar.
 *
 * Is build using a 'Plugin' like architecture, each plugin is called a Client
 * each of them are stored in crugeconnector.clients directory, by default
 * CrugeConnector provides clients to connect to Facebook an Google.
 *
 * If you need to develop a new Client please use the template named 'Tester'.
 *
 * DOCUMENTATION AND INSTRUCTIONS
 *	
 *  ***An extensive guide and how-to is provided on-line at: ***
 *  *** http://yiiframework.com/extensions/crugeconnector/  ***
 *
 *  the following information is provided as complementary:
 *
 * STEP1: Edit your config file and declare your clients:
 *	please locate it under 'components' key in your config/main:
 * <code>
 		'crugeconnector'=>array(
			'class'=>'ext.crugeconnector.CrugeConnector',
			'hostcontrollername'=>'site',
			'onSuccess'=>array('site/success'),
			'onError'=>array('site/error'),
			'clients'=>array(
				'facebook'=>array(
					// required by crugeconnector:
					'enabled'=>true,
					'class'=>'ext.crugeconnector.clients.Facebook',
					'callback'=>'http://dev.yiiframework.co/app/callback1.php',
					// required by remote interface:
					'client_id'=>"xxxxxxx",
					'client_secret'=>"yyyyyyyyyyyyyyyyyyyy",
					'scope'=>'email, read_stream',
				),	
				'google'=>array(
					// required by crugeconnector:
					'enabled'=>true,
					'class'=>'ext.crugeconnector.clients.Google',
					'callback'=>'http://dev.yiiframework.co/app/callback2.php',
					// required by remote interface:
					'hostname'=>'dev.yiiframework.co',
					'identity'=>'https://www.google.com/accounts/o8/id',
					'scope'=>array('contact/email'),
				),
				'tester'=>array(
					// required by crugeconnector:
					'enabled'=>true,
					'class'=>'ext.crugeconnector.clients.Tester',
					// required by remote interface:
				),
			),
		),
 * </code>
 *
 * STEP2 - Each connector needs a callback: create a copy of index.php and 
 * add this extra code:
 * <code>
 *		SEE README for an example
 *  </code>
 *
 *
 * STEP3 - in siteController.php add a new  static action:
 *
 *		public function actions() { 
 * 			return array('crugeconnector'=>array(
 *				'class'=>'CrugeConnectorAction')); 
 *		}
 *
 * @package CrugeConnector
 * @version 1.0
 * @author Christian Salazar <christiansalazarh@gmail.com>
 * @license http://opensource.org/licenses/bsd-license.php 
 */
class CrugeConnector extends CApplicationComponent {

	public $clients; 			// config/main indexed array args
	public $hostcontrollername;	// default: site
	public $actionconnector;	// default: crugeconnector
	public $onSuccess;			// ie: array('site/remoteloginsuccess')
	public $onError;			// ie: array('site/remoteloginerror')

	private $_baseUrl;

	public function init(){
		parent::init();
		$this->_publishAssets();
		$this->_defaults();
		Yii::app()->setImport(array('ext.crugeconnector.components.*'));
	}
	
	private function _defaults(){
		if(empty($this->hostcontrollername))
			$this->hostcontrollername = 'site';
		if(empty($this->actionconnector))
			$this->actionconnector = 'crugeconnector';
	}

	/**
	 * getStoredData 
	 *	retrieve the data stored in session by any connector client.
	 *	
	 * @access public
	 * @return mixed the stored data.
	 */
	public function getStoredData(){
		// please see also CrugeBaseClient
		return CrugeBaseClient::getStoredData();
	}

	private function args($ar){
		$txt = '';
		foreach($ar as $k=>$v)
			$txt .= "[{$k}={$v}]";
		return $txt;
	}

	/**
	 * execute 
	 *	a) called to start login process.
	 * 	b) called from remote website (callback url) to complete login flow
	 *	as an example:
	 *	http://yours.com/yourapp/callback-facebook.php
	 *	this script is internally preconfigured to call:
	 *	http://yours.com/yourapp/index.php?r=/site/crugeconnector&key=facebook
	 *	please note about "/site/crugeconnector": is your decision were to put
	 *	this action (crugeconnector), default is 'site', so this controller
	 *  must declare an static action: 
	 *		'crugeconnector'=>array('class'=>'CrugeConnectorAction'),		
	 * @see CrugeConnectorAction
	 * @param string $key Any configured key in main config. ie: 'facebook'
	 * @param string $mode must be 'login' or 'callback'
	 * @access protected called from CrugeConnectorAction
	 * @return void
	 */
	public function execute($key, $mode) {
		Yii::log(__METHOD__.','.$this->args(array('key'=>$key,'mode'=>$mode))
			,"crugeconnector");
		// no matter mode is login or callback, it always loads the 
		// client config for this key
		//
		$keyconfig = $this->getKeyExists($key);
		if($keyconfig == false)
			throw new Exception('the specified key is not configured. '.$key);
		if(!$this->isKeyEnabled($key))
			throw new Exception('the specified key is disabled. '.$key);

		// load the client and pass the config
		$client = $this->_findClient($key,$keyconfig);
		// _findClient throws an exception if something goes wrong.

		Yii::log(__METHOD__."[mode=".$mode."][_findClient_key=".$key."]","crugeconnector");

		// interface consumer:
		//
		$client->setKey($key);
		$client->setMode($mode);
		$client->setParameters($keyconfig);
		if($client->getIsLogin()){
			$client->response($client->doLogin()
					,$this->onSuccess, $this->onError);
		}elseif($client->getIsCallback()){
			$client->response($client->doCallback()
					,$this->onSuccess, $this->onError);
		}elseif($client->getIsError()){
			throw new CHttpException("Cannot login");
		}
		else
			throw new Exception('Invalid modality value, must be '.
			'login or callback. please review your callback configuration.');
	}

	// Ui Methods

	/**
	 * getHasEnabledClients 
	 *	 detects if your config specify any 'enabled' connector.
	 * @access public
	 * @return boolean true has enabled clients
	 */
	public function getHasEnabledClients(){
		foreach($this->clients as $k=>$v)
			if($v['enabled'] == true)
				return true;
		return false;
	}

	/**
	 * getEnabledClients 
	 *  get an $key array for each configured connector in config main.
	 * @access public
	 * @return array ie: array('facebook', 'google')
	 */
	public function getEnabledClients() {
		$a = array();
		foreach($this->clients as $k=>$v)
			if($v['enabled'] == true)
				$a[$k] = $v;
		return $a;
	}

	/**
	 * getClientDefaultImage 
	 *  returns the default image URL located in this component assets
	 * @param string $key Any configured key in main config. ie: 'facebook'
	 * @access public
	 * @return string the image URL returned by CHtml::normalizeUrl
	 */
	public function getClientDefaultImage($key){
		return $this->_baseUrl.'/'.$key.'.png';
	}

	/**
	 * getClientLoginUrl 
	 * 	return the URL to be used as a launcher for the selected connector
	 * @param string $key Any configured key in main config. ie: 'facebook'
	 * @access public
	 * @return string the login URL ready to be used in a CHtml::link.
	 */
	public function getClientLoginUrl($key) {
		if($c = $this->getKey($key)) {
			return array('/'.$this->hostcontrollername.'/'.$this->actionconnector
					,'crugekey'=>$key,'crugemode'=>'login');
		}else
		return '';
	}

	/**
	 * getKey 
	 *  get and checks for a key availability in config main.
	 * @param string $key Any configured key in main config. ie: 'facebook'
	 * @access public
	 * @return the key config array, or null otherwise.
	 */
	public function getKey($key){
		if($this->getKeyExists($key))
			return $this->isKeyEnabled($key);
		return false;
	}

	/**
	 * getKeyExists 
	 *	detects if a key is specified in config/main. 
	 * @param string $key Any configured key in main config. ie: 'facebook'
	 * @access public
	 * @return false or array(..key parameters..);
	 */
	public function getKeyExists($key){

		Yii::log(__METHOD__.','.$this->args(array('key'=>$key))
			,"crugeconnector");


		foreach($this->clients as $k=>$v)
			if($k == $key)
				return $v;
		return false;
	}

	/**
	 * isKeyEnabled 
	 *	check if an existing key is enabled (see getKeyExists)  
	 * @param string $key Any configured key in main config. ie: 'facebook'
	 * @access public
	 * @return false or true, or an exception if key doesn't exist.
	 */
	public function isKeyEnabled($key){
		return ($this->clients[$key]['enabled'] == true);
	}

	/**
	 * findClient
	 *	find and return the selected connector class by its $key 
	 * @param string $key Any configured key in main config. ie: 'facebook'
	 * @param array $keyconfig the provided config for this key (see getKey).
	 * @access private
	 * @return an instance which implements ICrugeClient
	 */
	private function _findClient($key, $keyconfig){

		Yii::log(__METHOD__.','.$this->args(array('key'=>$key))
			,"crugeconnector");


		$alias = $keyconfig['class'];
		$path = Yii::getPathOfAlias($alias).'.php';
		if(!file_exists($path))
			throw new Exception('file not found: '.$path);
		$className = ucfirst(strtolower($key));
		if(!@class_exists($className))
			include($path);
		if(!@class_exists($className))
			throw new Exception(
			  'the provided file does not contain a class named: '.$className);
		if(!is_subclass_of($className, 'CrugeBaseClient'))
			throw new Exception(
			  'the provided file does not extends from: CrugeBaseClient');
		$inst = new $className;
		if($inst == null)
			throw new Exception('cant instanciate '.$alias);
		$interfaceName = 'ICrugeClient';
		if($_impl = class_implements($inst))
			if(isset($_impl[$interfaceName]))
				return $inst;
		throw new Exception('the provided class does not implements '.
				'the required interface: '.$interfaceName);
	}

	private function _publishAssets(){
		$localAssetsDir = dirname(__FILE__) . '/resources';
		$this->_baseUrl = Yii::app()->getAssetManager()->publish(
				$localAssetsDir);
        $cs = Yii::app()->getClientScript();
		foreach(scandir($localAssetsDir) as $f){
			$_f = strtolower($f);
			if(strstr($_f,".swp"))
				continue;
			if(strstr($_f,".js"))
				$cs->registerScriptFile($this->_baseUrl."/".$_f);
			if(strstr($_f,".css"))
				$cs->registerCssFile($this->_baseUrl."/".$_f);
		}
	}
	
}
