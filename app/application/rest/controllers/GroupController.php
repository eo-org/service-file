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
		$orgCode = Class_Server::getOrgCode();
		
		$co = new Class_Mongo_Group_Co();
		$co->setFields(array('label', 'fileCount', 'orgCode'));
		
		$csu = Class_Session_User::getInstance();
		$co->addFilter('orgCode', $orgCode);
		
		$queryArray = array();
		
        $result = array();
		$data = $co->fetchAll(true);
		$dataSize = $co->count();
		
		$result['data'] = $data;
        $result['dataSize'] = $dataSize;
        $result['pageSize'] = $pageSize;
        $result['currentPage'] = $currentPage;
        
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
		$groupDoc->orgCode = Class_Server::getOrgCode();
		
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