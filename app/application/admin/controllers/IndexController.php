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
		$storageDoc = $storageCo->addFilter('orgCode',$miscFolder)->fetchOne();
		if(is_null($storageDoc)) {
			$fileCo = App_Factory::_m('File');
			$fileDoc = $fileCo->addFilter('orgCode',$miscFolder)->fetchDoc();
			$storageDoc = $storageCo->create();
			$storageDoc = $storageDoc->recalculateCapacity($fileDoc,$miscFolder);
		}
		$this->view->usedCapacity = $storageDoc->getStorageInfo($miscFolder);
	}
	
	
}