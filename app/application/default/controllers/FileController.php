<?php
class FileController extends Zend_Controller_Action 
{
	public function init()
	{
		$this->getResponse()->setHeader('Access-Control-Allow-Origin', '*');
	}
	
	public function resizeAction()
	{
		$fileServerKey = Class_Server::API_KEY;
		$result = 'success';
		$msg = array();
		
		$siteId = Class_Server::getSiteId();
		$urlname = $this->getRequest()->getParam('urlname');
		$toWidth = $this->getRequest()->getParam('toWidth');
		$toHeight = $this->getRequest()->getParam('toHeight');
		$fit = $this->getRequest()->getParam('fit');
		if(is_null($fit)) {
			$fit = 'fitToSize';
		}
		
		if(empty($toWidth) || empty($toHeight)) {
			$result = 'fail';
			$msg[] = 'dim is not set!';
			$this->_helper->json(array(
				'result' => $result,
				'msg' => $msg
			));
			exit(0);
		}
		
		$xTime = $this->getRequest()->getParam('xTime');
		$xSig = $this->getRequest()->getParam('xSig');
		$sig = md5($siteId.$xTime.$fileServerKey);
		
		if($sig != $xSig) {
			$result = 'fail';
			$msg[] = 'signature error! '.$xSig.' => '.$sig;
			$this->_helper->json(array(
				'result' => $result,
				'msg' => $msg
			));
			exit(0);
		}
		
		$fileCo = App_Factory::_m('File');
		$fileCo->addFilter('siteId', $siteId)
			->addFilter('urlname', $urlname);
		$fileDoc = $fileCo->fetchOne();
		if(is_null($fileDoc)) {
			$result = 'fail';
			$msg[] = 'file not found! '.$siteId.'/'.$urlname;
			$this->_helper->json(array(
				'result' => $result,
				'msg' => $msg
			));
			exit(0);
		}
		
		$ext = strtolower(pathinfo($urlname, PATHINFO_EXTENSION));
		
		$url = "http://storage.aliyun.com/public-misc/".$siteId."/".$urlname;
		file_put_contents(TMP_PATH.'/'.$urlname, file_get_contents($url));
		
		$filepath = TMP_PATH.'/'.$urlname;
		
		$newFileName = $toWidth.'_'.$toHeight.'_'.$urlname;
		$newFilePath = TMP_PATH.'/'.$newFileName;
		
		$ci = new Class_Image();
		$ci->readImage($filepath, $ext)
			->resize($toWidth, $toHeight, $fit)
			->writeImage($newFilePath, 100);
		$newFileContent = file_get_contents($newFilePath);
		
		$service = Class_Api_Oss_Instance::getInstance();
		$newFileResult = $service->createObject('public-misc', $siteId.'/_resize/'.$newFileName, $newFileContent);
		$newFileSize = filesize($newFilePath);
		unlink($filepath);
		unlink($newFilePath);
		
		$dims = $fileDoc->dims;
		if(is_null($dims)) {
			$dims = array();
		}
		$dimKey = $toWidth.'-'.$toHeight;
		$dims[$dimKey] = array(
			'urlname' => $newFileName,
			'size' => $newFileSize
		);
		$fileDoc->dims = $dims;
		$fileDoc->save();
		
		$this->_helper->json(array(
			'result' => $result,
			'msg' => $msg
		));
	}
}