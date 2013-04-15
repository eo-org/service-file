<?php
class Rest_FileController extends Zend_Rest_Controller 
{
	protected $_bucket;
	protected $_siteId;
	protected $_orgCode;
	protected $_userOrigName = false;
	
	public function init()
	{
		$csu = Class_Session_User::getInstance();
		
		$this->_bucket = 'public-misc';
		$this->_orgCode = Class_Server::getOrgCode();
		$this->_siteId = Class_Server::getSiteId();
		
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout()->disableLayout();
	}
	
	public function indexAction()
	{
		$pageSize = 8;
		$currentPage = 1;
		$co = new Class_Mongo_File_Co();
		$co->setFields(array('filename', 'size', 'isImage', 'uploadTime', 'urlname', 'groupId', 'orgCode'));
		$queryArray = array();
		
        $result = array();
        foreach($this->getRequest()->getParams() as $key => $value) {
			switch($key) {
                	case 'groupId':
                		$co->addFilter('groupId', $value);
//                		if($value != 'ungrouped') {
//                			$co->addFilter('groupId', $value);
//                		} else {
//                			$co->addFilter('groupId', 'ungrouped');
//                		}
                		break;
                    case 'page':
            			if(intval($value) != 0) {
            				$currentPage = $value;
            			}
                        $result['currentPage'] = intval($value);
            		    break;
			}
        }
        
        $csu = Class_Session_User::getInstance();
		$co->addFilter('siteId', $this->_siteId)
			->setPage($currentPage)->setPageSize($pageSize)
			->sort('_id', -1);
		$data = $co->fetchAll(true);
		$dataSize = $co->count();
		
		$result['data'] = $data;
        $result['dataSize'] = $dataSize;
        $result['pageSize'] = $pageSize;
        $result['currentPage'] = $currentPage;
        $this->_helper->json($result);
	}
	
	public function getAction()
	{
		
	}
	
	public function postAction()
	{
		$csu = Class_Session_User::getInstance();
		$service = Class_Api_Oss_Instance::getInstance();
		
		if($this->getRequest()->isPost()) {
			$siteId = Class_Server::getSiteId();
			
			$storageCo = App_Factory::_m('Storage');
			$storageDoc = $storageCo->addFilter('siteId', $siteId)->fetchOne();
			if($storageDoc->checkCapacity()){
				$groupId = $this->getRequest()->getParam('groupId');
				$useOrigName = false;
				if($groupId == 'system') {
					$useOrigName = true;
				}
				$uploadedFile = $_FILES['uploadedfile'];
				$filename = $uploadedFile['name'];
				$tmpName = $uploadedFile['tmp_name'];
				$size = $uploadedFile['size'];
				if($size > 26000000) {
		            $result = 'fail';
		            $msg[] = 'file size exceeding 25MB!';
		        }
				$fileContent = file_get_contents($tmpName);
				
				$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
				$uploadUnixTime = time();
				if($useOrigName) {
					$urlname = $filename;
				} else {
					$urlname = md5($uploadUnixTime.$size).'.'.$ext;
				}
				
				$isImageFile = false;
				$fileType = App_File::getMimetype($ext);
				if(in_array($fileType, array('image/jpeg', 'image/gif', 'image/png'))) {
					$isImageFile = true;
					$isFileType = 'image';
				} else if(in_array($fileType, array('video/3gpp', 'audio/mpeg', 'video/mp4','video/mpeg','video/ogv','video/x-ms-wmv'))) {
					$isFileType = 'video';
				} else {
					$isFileType = 'other';
				}
				
				if($isImageFile) {
					//internal use, used as thumb in file controller system
					$thumbname = TMP_PATH.'/'.$urlname;
					$ci = new Class_Image();
					$ci->readImage($tmpName, $ext)
						->resize(120, 120, Class_Image::FIT_TO_FRAME)
						->writeImage($thumbname, 60);
					$thumbContent = file_get_contents($thumbname);
					$thumbResult = $service->createObject($this->_bucket, $this->_siteId.'/_thumb/'.$urlname, $thumbContent);
					unlink($thumbname);
				}
				$result = $service->createObject($this->_bucket, $this->_siteId.'/'.$urlname, $fileContent, $size);
				
				$createNewDoc = true;
				
				if($useOrigName) {
					$fileDoc = App_Factory::_m('File')->addFilter('urlname', $filename)
						->addFilter('groupId', 'system')
						->addFilter('siteId', $this->_siteId)
						->fetchOne();
					if(!is_null($fileDoc)) {
						$createNewDoc = false;
					}
				}
				
				if($createNewDoc) {
					$fileDoc = App_Factory::_m('File')->create(array(
						'orgCode' => $this->_orgCode,
						'siteId' => $this->_siteId,
						'userId' => $csu->getUserId(),
						'groupId' => $groupId,
						'filename' => $filename,
						'urlname' => $urlname,
						'size' => $size,
						'storage' => $service->getProvider(),
						'isImage' => $isImageFile,
						'uploadUnixTime' => $uploadUnixTime,
						'uploadTime' => date('Y-m-d H:i:s', $uploadUnixTime)
					));
					$fileDoc->save();
					
					$storageDoc->addFile($fileDoc);
					
					if($groupId != 'system' && $groupId != 'ungrouped') {
						$groupDoc = App_Factory::_m('Group')->find($groupId);
						$groupDoc->fileCount++;
						$groupDoc->save();
					}
				} else {
					$fileDoc->setFromArray(array(
						'size' => $size,
						'uploadUnixTime' => $uploadUnixTime,
						'uploadTime' => date('Y-m-d H:i:s', $uploadUnixTime)
					));
					$fileDoc->save();
				}
				$this->getResponse()->setHeader('result', 'sucess');
				$this->_helper->json($fileDoc->toArray(true));
			}else{
				 $msg[] = "上传失败，空间不足";
				 $this->getResponse()->setHeader('result', 'fail');
				 $this->_helper->json($msg);
			}
		}
		
	}
	
	public function putAction()
	{
		$modelString = $this->getRequest()->getParam('model');
		$jsonArry = Zend_Json::decode($modelString);
		
		$fileDoc = App_Factory::_m('File')->find($jsonArry['id']);
		if($fileDoc->groupId != $jsonArry['groupId']){
			$groupCo = App_Factory::_m('Group');
			
			if($fileDoc->groupId != 'ungrouped' && $fileDoc->groupId != 'system') {
				$fromGroupDoc = $groupCo->find($fileDoc->groupId);
				$fromGroupDoc->fileCount = $fromGroupDoc->fileCount - 1;
				$fromGroupDoc->save();
			}
			
			$toGroupDoc = $groupCo->find($jsonArry['groupId']);
			$toGroupDoc->fileCount = $toGroupDoc->fileCount + 1;
			$toGroupDoc->save();
		}
		$fileDoc->setFromArray($jsonArry);
		$fileDoc->save();
		$this->getResponse()->setHeader('result', 'sucess');
		$this->_helper->json(array('id' => $fileDoc->getId()));
	}
	
	public function deleteAction()
	{
		$csu = Class_Session_User::getInstance();
		$fileId = $this->getRequest()->getParam('id');
		$fileDoc = App_Factory::_m('File')->find($fileId);
		
		if($fileDoc != null && $fileDoc->orgCode == $this->_orgCode) {
			$objectUrl = $fileDoc->urlname;
			$groupId = $fileDoc->groupId;
			
			$service = Class_Api_Oss_Instance::getInstance();
			$service->removeObject($this->_bucket, $this->_siteId.'/'.$objectUrl);
			if($fileDoc->isImage === true) {
				$service->removeObject($this->_bucket, $this->_siteId.'/_thumb/'.$objectUrl);
			}
			
			$siteId = Class_Server::getSiteId();
		
			$storageCo = App_Factory::_m('Storage');
			$storageDoc = $storageCo->addFilter('siteId', $siteId)->fetchOne();
			$storageDoc->removeFile($fileDoc);
			
			$fileDoc->delete();
			
			if(!empty($groupId) && $groupId != 'ungrouped' && $groupId != 'system') {
				$groupDoc = App_Factory::_m('Group')->find($groupId);
				$groupDoc->fileCount--;
				$groupDoc->save();
			}
			$this->getResponse()->setHeader('result', 'sucess');
		} else {
			$this->getResponse()->setBody($fileDoc->orgCode.' != '.$this->_orgCode);
			$this->getResponse()->setHeader('result', 'fail');
		}
	}
	
	public function recalculateAction()
	{
		$fileCo = App_Factory::_m('File');
		$fileDoc = $fileCo->addFilter('orgCode',$this->_orgCode)->fetchDoc();
// 		$storageCo = App_Factory::_m('Storage');
// 		$storageDoc = $storageCo->addFilter('orgCode',$this->_orgCode)->fetchOne();
		$siteId = Class_Server::getSiteId();
		
		$storageCo = App_Factory::_m('Storage');
		$storageDoc = $storageCo->addFilter('siteId', $siteId)->fetchOne();
		if(is_null($storageDoc)) {
			$storageDoc = $storageCo->create();
			$storageDoc = $storageDoc->recalculateCapacity($fileDoc,$this->_orgCode);
		}
		echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' >";
		echo "拥有".$storageDoc->imageFileCount."个图片文件，".$storageDoc->otherFileCount."个其他文件，共用".round($storageDoc->usedCapacity/1024,2)."MB";
		exit;
	}
}