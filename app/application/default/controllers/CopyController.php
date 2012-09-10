<?php
class CopyController extends Zend_Controller_Action 
{
	public function toSiteAction()
	{
		$fileServerKey = Class_Server::API_KEY;
		$result = 'success';
		$msg = array();
		
		$fromId = Class_Server::getSiteId();
		$toSiteId = $this->getRequest()->getParam('id');
		
		$xSig = $this->getRequest()->getHeader('X-Sig');
		$xTime = $this->getRequest()->getHeader('X-Time');
		$sig = md5($fromId.$xTime.$toSiteId.$fileServerKey);
		if($sig != $xSig) {
			$result = 'fail';
			$msg[] = 'signature error! '.$xSig.' => '.$sig;
			$this->_helper->json(array(
				'result' => $result,
				'msg' => $msg
			));
			exit(0);
		}
		
		$fromSiteDoc = Class_RemoteServer::getSiteDoc($fromId);
		$toSiteDoc = Class_RemoteServer::getSiteDoc($toSiteId);
		if(is_null($fromSiteDoc) || is_null($toSiteDoc)) {
			$result = 'fail';
			$msg[] = 'site not found on account server: ('.$fromId.' => '.$toSiteId.')';
		} else {
			$toOrgCode = $toSiteDoc->orgCode;
			
			$fileCo = App_Factory::_m('File');
			$fileCo->addFilter('siteId', $fromId);
			if(false) {
				$fileCo->addFilter('groupId', 'system');
			}
			$fileDocsToCopy = $fileCo->fetchDoc();
			
			foreach($fileDocsToCopy as $docToCopy) {
				$r = $fileCo->copyFile($docToCopy, $toSiteId, $toOrgCode);
				if($r !== true) {
					$msg[] = $r;
				}
			}
		}
		
		$this->_helper->json(array(
			'result' => $result,
			'msg' => $msg
		));
	}
}