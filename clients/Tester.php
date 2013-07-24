<?php 
/**
 * Tester
 *	a tester class to demostrate the minimum required to give response to
 *	your local system when a remote login is required.
 * 
 *	how to:
 *	 know which client am i ?  $this->getKey() // returns: 'facebook'
 *	 know the current config for this client:  $this->getParameters();
 *
 * @uses CrugeBaseClient
 * @package CrugeConnector
 * @version 1.0
 * @author Christian Salazar <christiansalazarh@gmail.com>
 * @license http://opensource.org/licenses/bsd-license.php 
 */
class Tester  extends CrugeBaseClient 
	implements ICrugeClient 
{
	/**
	 * doLogin 
	 *		you must redirect your browser to the website auth provider.
	 * @access public
	 * @return boolean true or false (call setLastError) 
	 */
	public function doLogin(){
		//header('Location: http://google.com/');	
		// if you decide to redirect browser you must ends with exit().
		return $this->doCallback();
	}

	/**
	 * doCallback 
	 * 		you must process a calilback response comming from host auth provider.
	 *
	 *	you pass remote data to your local system using:
	 *		$this->setData($anydata);
	 *		$this->setLastError('error message');
	 *
	 * @access public
	 * @return bool boolean result true if login is correct.
	 */
	public function doCallback(){
		Yii::log(__METHOD__."[docallback invoked for tester]","crugeconnector");

		// you can enable or disable any of this cases:
		
		// google simulation:
		//
		/*
		$this->setKey('google');
		$this->setData(CJSON::encode(
			array('contact/email'=>'jondoe@xyz.com')));
		*/

		// facebook simulation: 
		//
		$this->setKey('facebook');
		$this->setData(CJSON::encode(
			array(
				'username'=>'jdoe',
				'email'=>'jondoe@xyz.com',
				'first_name'=>'Jon',
				'last_name'=>'Doe',
			)));

		return true;

		// error simulation:
		//
		/*
		$this->setLastError('some error here');
		return false;
		*/
	}
}
