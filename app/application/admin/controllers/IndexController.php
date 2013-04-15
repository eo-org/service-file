<?php
class Admin_IndexController extends Zend_Controller_Action 
{
	public function indexAction()
	{
		$orgCode = Class_Server::getOrgCode();
		$siteId = Class_Server::getSiteId();
		
		$csu = Class_Session_User::getInstance();
		$this->view->siteId = $siteId;
		$this->view->siteOrgCode = $orgCode;
		$this->view->orgCode = $csu->getUserData('orgCode');
		$this->view->userId = $csu->getUserId();
		$this->view->loginName = $csu->getUserData('loginName');
		
		$this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
		
		$storageCo = App_Factory::_m('Storage');
		$storageDoc = $storageCo->addFilter('orgCode', $orgCode)->fetchOne();
		
		echo $orgCode;
		
		if(is_null($storageDoc)) {
			$fileCo = App_Factory::_m('File');
			$fileDoc = $fileCo->addFilter('orgCode', $orgCode)->fetchDoc();
			$storageDoc = $storageCo->create();
			$storageDoc = $storageDoc->recalculateCapacity($fileDoc, $orgCode);
		}
		$this->view->usedCapacity = $storageDoc->getStorageInfo($orgCode);
	}
}
