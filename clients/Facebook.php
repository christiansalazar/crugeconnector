<?php 
/**
 * Facebook
 *
 * @uses CrugeBaseClient
 * @package CrugeConnector
 * @version 1.0
 * @author Christian Salazar <christiansalazarh@gmail.com>
 * @license http://opensource.org/licenses/bsd-license.php 
 */
class Facebook extends CrugeBaseClient 
	implements ICrugeClient 
{
	/**
	 * doLogin 
	 *		you must redirect your browser to the website auth provider.
	 * @access public
	 * @return boolean true or false (setLastError) 
	 */
	public function doLogin(){
		if(empty($_REQUEST['code'])){
			$p = $this->getParameters();
			$s = new CHttpSession();
			$s->open();
			$state = md5(uniqid(rand(),TRUE));
			//$_SESSION['state'] = $state;
			$s['state']=$state;
			$dialog_url  = 'https://www.facebook.com/dialog/oauth?';
			$dialog_url .= '&client_id='.$p['client_id'];
			$dialog_url .= '&scope='.$p['scope'];
			$dialog_url .= '&redirect_uri='.urlencode($p['callback']);
			$dialog_url .= '&state='.$state;
			$s->close();
			header('Location: '.$dialog_url);
			echo("<script> top.location.href='" . $dialog_url . "'</script>");
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
		if(isset($_REQUEST['error']))
			if($_REQUEST['error']=='access_denied') {
				$this->setLastError($_REQUEST['error_description']);
				return false;
			}
		$s = new CHttpSession();
		$s->open();
		if(isset($s['state'])){
			if($s['state'] === $_REQUEST['state']){
				$ac = $this->_getAccessToken($_REQUEST['code']);	
				$this->setData($this->_requestUser($ac));
				$s->close();
				return true;
			}
			else{
				$this->setLastError('CRSF validation failed.');
				$s->close();
				return false;
			}
		}
		else{
			$this->setLastError('invalid state argument.');
			$s->close();
			return false;
		}
	}

	private function _getAccessToken($code) {
		$p = $this->getParameters();
		$token_url = "https://graph.facebook.com/oauth/access_token?"
       		. "client_id=" . $p['client_id'] 
	   		. "&redirect_uri=" . urlencode($p['callback'])
	   		. "&client_secret=" . $p['client_secret']
	   		. "&code=" . $code;
		$params = null;
		$response = file_get_contents($token_url);
		parse_str($response, $params);
		return $params['access_token'];
	}
	
	private function _requestUser($accessToken) {
		return file_get_contents(
		  "https://graph.facebook.com/me?access_token=".$accessToken
		); 
	}


}
