<?php
class Admin_IndexController extends Zend_Controller_Action 
{
	public function indexAction()
	{
		$csu = Class_Session_User::getInstance();
		$miscFolder = Class_Server::getOrgCode();
		
		$this->view->miscFolder = $miscFolder;
		$this->view->orgCode = $csu->getUserData('orgCode');
		$this->view->userId = $csu->getUserId();
		$this->view->loginName = $csu->getUserData('loginName');
		
		$this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
		
		$storageCo = App_Factory::_m('Storage');
		$storageDoc = $storageCo->addFilter('orgCode',Class_Server::getOrgCode())->fetchOne();
		$this->view->usedCapacity = $storageDoc->getStorageInfo($miscFolder);
	}
	
	
}