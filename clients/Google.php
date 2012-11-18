<?php 
/**
 * Google 
 *
 *	dependencies:
 *		../components/LightOpenID.php
 *
 * @uses CrugeBaseClient
 * @package CrugeConnector
 * @version 1.0
 * @author Christian Salazar <christiansalazarh@gmail.com>
 * @license http://opensource.org/licenses/bsd-license.php 
 */
class Google  extends CrugeBaseClient 
	implements ICrugeClient 
{
	private $_openid;
	private function _getOpenId(){
		if($this->_openid == null){
			$p = $this->getParameters();
			$this->_openid = new LightOpenID($p['hostname']);
			return $this->_openid;
		}
		else
		return $this->_openid;
	}

	/**
	 * doLogin 
	 *		you must redirect your browser to the website auth provider.
	 * @access public
	 * @return boolean true or false (call setLastError) 
	 */
	public function doLogin(){
		$p = $this->getParameters();
		$openid = $this->_getOpenId();
		if(!$openid->mode) {
			$openid->identity = $p['identity'];
			$openid->required = $p['scope'];
			$openid->returnUrl = $p['callback'];
			header('Location: ' . $openid->authUrl());
			exit();
		}
		else
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
		$p = $this->getParameters();
		$openid = $this->_getOpenId();
		if($openid->mode) {
			if($openid->mode == 'cancel'){
				$this->setLastError('the user aborts the operation.');
				return false;
			}
			else{
				if($openid->validate()){
					if($openid->identity){
						$att = $openid->getAttributes();
						$this->setData(CJSON::encode($att));
						return true;
					}
					else{
						$this->setLastError('cannot logon');
						return false;
					}
				}
				else{
					// no se valida la sesion
					$this->setLastError('cannot validate your account');
					return false;
				}
			}
		}else{
			$this->setLastError('invalid login information');
			return false;
		}
	}
}
