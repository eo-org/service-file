<?php
class Admin_IndexController extends Zend_Controller_Action 
{
	public function indexAction()
	{
		$csu = Class_Session_User::getInstance();
		$presetOrgCode = $this->getRequest()->getParam('orgCode');
		if(!is_null($presetOrgCode)) {
			Class_Session_User::setOrgCode($presetOrgCode);
		}
		$miscFolder = $csu->getOrgCode();
		if(empty($miscFolder)) {
			throw new Exception('organization folder path required!');
		}
		
		$this->view->miscFolder = $miscFolder;
		$this->view->orgCode = $csu->getUserData('orgCode');
		$this->view->userId = $csu->getUserId();
		$this->view->loginName = $csu->getUserData('loginName');
		$this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
	}
}