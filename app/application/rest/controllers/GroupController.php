<?php
class Rest_GroupController extends Zend_Rest_Controller 
{
	public function init()
	{
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
	}
	
	public function indexAction()
	{
		$siteId = Class_Server::getSiteId();
		
		$co = new Class_Mongo_Group_Co();
		$co->setFields(array('label', 'fileCount', 'siteId'));
		
		$csu = Class_Session_User::getInstance();
		$co->addFilter('siteId', $siteId);
		
		$queryArray = array();
		
        $result = array();
		$data = $co->fetchAll(true);
		$dataSize = $co->count();
		
		$result['data'] = $data;
        $result['dataSize'] = $dataSize;
        
        return $this->_helper->json($result);
	}
	
	public function getAction()
	{
	}
	
	public function postAction()
	{
		$modelString = $this->getRequest()->getParam('model');
		$jsonArry = Zend_Json::decode($modelString);
		$groupDoc = App_Factory::_m('Group')->create($jsonArry);
		
		$csu = Class_Session_User::getInstance();
		$groupDoc->userId = $csu->getUserId();
		$groupDoc->siteId = Class_Server::getSiteId();
		
		$groupDoc->save();
		$this->getResponse()->setHeader('result', 'sucess');
		$this->_helper->json(array('id' => $groupDoc->getId()));
	}
	
	public function putAction()
	{
		$modelString = $this->getRequest()->getParam('model');
		$jsonArry = Zend_Json::decode($modelString);
		
		$groupDoc = App_Factory::_m('Group')->find($jsonArry['id']);
		$groupDoc->setFromArray($jsonArry);
		$groupDoc->save();
		
		$this->getResponse()->setHeader('result', 'sucess');
		$this->_helper->json(array('id' => $groupDoc->getId()));
	}
	
	public function deleteAction()
	{
		$id = $this->getRequest()->getParam('id');
		
		$groupDoc = App_Factory::_m('Group')->find($id);
		
		$this->getResponse()->setHeader('result', 'fail');
		if(!is_null($groupDoc)) {
			$fileCollection = App_Factory::_m('File');
			$fileCollection->update(
				array('groupId' => $id),
				array('$set' => array('groupId' => 'ungrouped')),
				array('multiple' => true)
			);
			$groupDoc->delete();
			$this->getResponse()->setHeader('result', 'sucess');
		}
	}
}