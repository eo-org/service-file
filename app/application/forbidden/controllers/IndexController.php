<?php
class Forbidden_IndexController extends Zend_Controller_Action
{
	public function init()
    {
    	$this->getResponse()->setHttpResponseCode(403);
    }
    
	public function indexAction()
	{
		
	}
	
	public function notResourceOwnerAction()
	{
		
	}
}