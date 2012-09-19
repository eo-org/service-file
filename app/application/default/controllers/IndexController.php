<?php
class IndexController extends Zend_Controller_Action 
{
	public function indexAction()
    {
    	
    }
    
    public function infoAction()
    {
    	$csu = Class_Session_User::getInstance();
		$userData = $csu->getUserData();
		
		if($userData['userType'] == 'designer') {
			
		} else {
			
		}
		
		$this->view->userData = $userData;
    }
}