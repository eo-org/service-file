<?php
class ErrorController extends Zend_Controller_Action
{

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        $this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
        $this->view->headTitle("页面不存在");
        
        switch($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                $this->render('404');
                break;
            default:
                switch(get_class($errors->exception)) {
                    case 'Class_Exception_Pagemissing':
                        $this->render('404');
                        break;
                    case 'Class_Model_Product_Stock_Exception':
                        $this->render('stock');
                        break;
                    case 'Class_Model_Category_Exception':
                        $this->view->headTitle("目录不存在");
                        $this->render('category');
                        break;
                    default:
                        $exception = $errors->exception;
//                        Class_Core::log($exception, 'error');
                        $this->view->message = $exception;
                        echo $exception;
                        $this->render('error');
                        break;
                }
                break;
        }
        echo 'in error controller';
    }
    
    public function expiredAction()
    {
        
    }
}
