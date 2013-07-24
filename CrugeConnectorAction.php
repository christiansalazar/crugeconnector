<?php
 /**
 * CrugeConnectorActioni
 *
 *	setup:
 *		// in any Controller:
 *		public function actions() { 
 * 			return array('crugeconnector'=>array(
 *				'class'=>'CrugeConnectorAction')); 
 *		}
 * @package CrugeConnector 
 * @author Christian Salazar <christiansalazarh@gmail.com>
 * @license http://opensource.org/licenses/bsd-license.php
 */
class CrugeConnectorAction extends CAction {
	public function run(){
		$key = $_GET['crugekey'];
		$mode = $_GET['crugemode'];
		Yii::log(__METHOD__.', key='.$key.', mode='.$mode,'crugeconnector');	
		//$inst = new CrugeConnector();
		$inst = Yii::app()->crugeconnector;
		$inst->execute($key, $mode);
	}
 }

