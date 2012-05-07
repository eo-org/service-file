<?php
class Admin_SystemController extends Zend_Controller_Action 
{
	public function indexAction()
	{
		$csu = Class_Session_User::getInstance();
		if($csu->getUserData('userType') == 'eo-root') {
			$miscFolder = $csu->getUserData('orgCode');
		} else {
			$miscFolder = $csu->getUserData('orgCode');
		}
		if(empty($miscFolder)) {
			throw new Exception('organization folder path required!');
		}
		
		$this->view->miscFolder = $miscFolder;
		$this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
	}
}