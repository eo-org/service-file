<?php
class Admin_AdminController extends Zend_Controller_Action 
{
	public function indexAction()
	{
		$csu = Class_Session_User::getInstance();
		$userData = $csu->getUserData();
		
		if($userData['userType'] == 'designer') {
			
		} else {
			
		}
		
		$this->view->userData = $userData;
	}
}