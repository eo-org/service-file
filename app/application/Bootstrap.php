<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initAutoloader()
	{
		$autoloader = Zend_Loader_Autoloader::getInstance();
		$autoloader->registerNamespace('Class_');
		$autoloader->registerNamespace('Twig_');
		$autoloader->registerNamespace('App_');
	}

	protected function _initDb()
	{
		$mongoDb = new App_Mongo_Db_Adapter('file', Class_Server::getMongoServer());
		App_Mongo_Db_Collection::setDefaultAdapter($mongoDb);
	}

	protected function _initSession()
	{
		Zend_Session::start();
	}

	protected function _initController()
	{
		Zend_Controller_Action_HelperBroker::addPath(APP_PATH.'/helpers', 'Helper');
		$controller = Zend_Controller_Front::getInstance();
		$controller->setControllerDirectory(array(
            'default' => APP_PATH.'/default/controllers',
			'admin' => APP_PATH.'/admin/controllers',
			'rest' => APP_PATH.'/rest/controllers'
		));
		
//		$controller->registerPlugin(new Class_Plugin_Acl());
		$csu = Class_Session_User::getInstance();
		$controller->registerPlugin(new App_Plugin_BackendSsoAuth(
        	$csu,
        	App_Plugin_BackendSsoAuth::SERVICE_FILE,
        	Class_Server::API_KEY
        ));
		$controller->throwExceptions(true);
		Zend_Layout::startMvc();
		$layout = Zend_Layout::getMvcInstance();
		$layout->setLayout('template');
	}

	protected function _initRouter()
	{
		$controller = Zend_Controller_Front::getInstance();
		$router = $controller->getRouter();
		
		$defaultRoute = new Zend_Controller_Router_Route(
			':siteId/:module/:controller/:action/*',
			array(
				'module'     => 'admin',
				'controller' => 'index',
				'action'     => 'index'
			),
			array('siteId' => '([a-z0-9-]+)')
		);
		$router->addRoute('default', $defaultRoute);
		
		$orgRoute = new Zend_Controller_Router_Route_Static(
			'info',
			array(
				'module' => 'default',
				'controller' => 'index',
				'action' => 'info'
			)
		);
		$router->addRoute('admin', $orgRoute);
		
		$router->addRoute('rest', new Zend_Rest_Route($controller, array(), array('rest')));
        unset($router);
	}
}