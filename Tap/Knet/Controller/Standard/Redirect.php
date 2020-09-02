<?php

namespace Tap\Knet\Controller\Standard;

class Redirect extends \Tap\Knet\Controller\Knet
{
    public function execute()
    {

        $order = $this->getOrder();
        $charge_url = $this->getKnetModel()->knetRequest($order);
        $comment = 'Customer was redirected to KNET';
        $this->addOrderHistory($order,$comment);
        return $this->chargeRedirect($charge_url);
    }

    public function chargeRedirect($url){
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($url);
        return $resultRedirect;
        
    }
  

}

