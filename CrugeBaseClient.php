<?php 
/**
 * CrugeBaseClient 
 * 
 * @package CrugeConnector
 * @author Christian Salazar <christiansalazarh@gmail.com>
 * @license http://opensource.org/licenses/bsd-license.php
 */
abstract class CrugeBaseClient {

	private $config;
	private $key;
	private $mode;
	private $data;
	private $lasterror;

	private static $_sessionkeyname = '_crugeconnectordata_';

	/**
	 * setKey 
	 *	called from CrugeConnector::run to set the key for this Client 
	 * @param string $key The config key name
	 * @access public
	 * @return void
	 */
	public function setKey($key){
		$this->key = $key;
	}
	public function getKey(){
		return $this->key;
	}

	/**
	 * setMode
	 *	called from CrugeConnector::run to set the mode for this Client,
	 *	it must be one of: 
	 *		'login': to inform about this is the login launch
	 *		'callback': to inform about this is a callback call
	 * @param string $mode 'login' or 'callback'
	 * @access public
	 * @return void
	 */
	public function setMode($mode){
		$this->mode = $mode;
	}
	public function getIsLogin(){
		return $this->mode=='login';
	}
	public function getIsCallback(){
		return $this->mode=='callback';
	}
	public function getIsError(){
		return $this->mode=='error';
	}

	/**
	 * setParameters 
	 *	CrugeConnector pass parameters specified in config/main to this
	 *  client using this method.
	 * @param array $config an indexed array (the config/main for this key)
	 * @access public
	 * @return void
	 */
	public function setParameters($config){
		$this->config = $config;
	}
	public function getParameters(){
		return $this->config;
	}


	/**
	 * setData 
	 * 	saves the data to be sent as a response to your local server
	 * @param mixed $anyData 
	 * @access public
	 * @return void
	 */
	public function setData($anyData) {
		$this->data = $anyData;
		$this->push();
	}

	private function push(){
		Yii::app()->session['__crugeconnector_data__'] = $this->getData();
	}
	public static function getStoredData() {
		if(isset(Yii::app()->session['__crugeconnector_data__']))
		  return Yii::app()->session['__crugeconnector_data__'];
    	return null;
    }

	public function getData(){
		return $this->data;
	}

	public function setLastError($txt){
		$this->lasterror = $txt;
	}
	public function getLastError(){
		return $this->lasterror;
	}

	/**
	 * response 
	 *	goes back to your application, containing  the returned data from a 
	 *	 remote site or any error information obtained from it.
	 * @param boolean $boolResult  true (authok) or false(error)
	 * @param mixed $data depends on remote server and the boolResult flag
	 * @access protected called only from CrugeConnector::execute
	 * @return void
	 */



	/**
	 * response 
	 * 	return the control to your application using the provided boolean result
	 *	and data, including lasterror. It will redirect the control to an
	 *  action specified by: onSuccess or onError, depends on boolResult.
	 *	
	 *	when boolResult is TRUE 	(redirected to onSuccess url)
	 *	You can read data using Yii::app()->crugeconnector->getStoredData();
	 *
	 *	when boolREsult is FALSE  	(redirected to onError url)
	 *	the return url onError receive an extra argument: &message=<lasterror>.
	 *
	 * @param mixed $boolResult 
	 * @param mixed $onSuccess 	array URL
	 * @param mixed $onError 	array URL
	 * @access public	Only CrugeConnector::execute use this method.
	 * @return void perform redirect to onSuccess or onError Url's.
	 */
	public function response($boolResult, $onSuccess, $onError){
		$url_dest = '';

		$url = $onError;
		if($boolResult == true)
			$url = $onSuccess;

		$url['key']=$this->getKey(); 
		$url_dest = CHtml::normalizeUrl($url);

		Yii::log(__METHOD__.",[BUILD_URL] url_dest=[{$url_dest}]","crugeconnector");

		// if an error is present then must send the error via url
		//
		if($boolResult == false)
			$url_dest .= '&message='.CHtml::encode($this->getLastError());

		header('Location: '.$url_dest);
	}
} 
