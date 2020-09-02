<?php 

class Tap_TapCheckout_SharedController extends Tap_TapCheckout_Controller_Abstract
{
   
    protected $_redirectBlockType = 'tapcheckout/shared_redirect';
    protected $_paymentInst = NULL;
	
	
	public function  successAction()
    {
    	// Mage::getModel('sales/quote')->load($order->getQuoteId())->setIsActive(false)->save();
    	// $session = Mage::getSingleton('checkout/session');
    	// $order = Mage::getSingleton('sales/order');
    	// $last = $order->$session->getLastRealOrderId();
    	// var_dump($last);exit;
    	// $order->loadByIncrementId($session->getLastRealOrderId());
    	//var_dump($order);exit;
    	$pageName = 'checkout/onepage/success/';
    	$params['_secure'] = true;
        $this->_redirect($pageName,$params);
        // $session = Mage::getSingleton('checkout/session');
        // $session->setQuoteId($session->getLastSuccessQuoteId());
        // Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        // $this->_redirect('checkout/onepage/success', array('_secure'=>true));
    }
	
	
	
	 public function failureAction()
    {
       
	   $arrParams = $this->getRequest()->getPost();
	   Mage::getModel('tapcheckout/shared')->getResponseOperation($arrParams);
       $this->getCheckout()->clear();
	   $this->_redirect('checkout/onepage/failure');
    }


    public function canceledAction()
    {
	    $arrParams = $this->getRequest()->getParams();
	
       
		Mage::getModel('tapcheckout/shared')->getResponseOperation($arrParams);
		
		$this->getCheckout()->clear();
		$this->loadLayout();
        $this->renderLayout();
    }


   

    
}
    
    