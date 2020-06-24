<?php

namespace Gateway\Tap\Controller\Standard;

class Redirect extends \Gateway\Tap\Controller\Tap
{
    public function execute()
    {
        $token_returned = $_REQUEST['token'];
        if (empty($token_returned)) {
           $fail_url = $this->getTapHelper()->getUrl('checkout/cart');
           $qoute = $this->getQuote();
                $this->getCheckoutSession()->restoreQuote($qoute);
                $qoute->setIsActive(true);
            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addError(__("Transaction Failed"));
            return $resultRedirect->setUrl($fail_url);
           //$resultRedirect->setUrl($fail_url);

        }
        else {
            $order = $this->getOrder();
            $charge_url = $this->getTapModel()->redirectMode($order, $_REQUEST['token'] );
            if ($order->getBillingAddress())
            {
                $this->addOrderHistory($order,'<br/>On site payment');
                
            }
            return $this->chargeRedirect($charge_url);
        }
    }

    public function chargeRedirect($url){
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($url);
        return $resultRedirect;
        
    }

}