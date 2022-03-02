<?php

namespace Gateway\Tap\Controller\Standard;

class Redirect extends \Gateway\Tap\Controller\Tap
{
    public function execute()
    {
		$popup = false;
        if (isset($_GET['token'])) {
            $source_id = $_GET['token'];
        }
        else if (isset($_GET['knet'])) {
            $source_id = 'src_kw.knet';
        }
        else if (isset($_GET['benefit'])) {
            $source_id = 'src_bh.benefit';
        }
        else if(isset($_GET['redirect'])){
            $source_id = 'src_all';
        }
        else if (isset($_GET['applepay'])) {
            $source_id = 'src_apple_pay';
        }
        else {
            $source_id = 'src_all';
            $popup = true;
        }
        $order = $this->getOrder();
        $orderId = $order->getIncrementId();
        if ($source_id == 'src_all' && $popup == true) {
            $data = $this->getTapModel()->redirectMode($order,$source_id);
            $result = $this->jsonResultFactory->create();
            $result->setData($data);
            return $result;
        }

        $order = $this->getOrder();
		$orderId = $order->getIncrementId(); 
        if ($order->getBillingAddress())
        {
            $charge_url = $this->getTapModel()->redirectMode($order,$source_id);
            if ($charge_url == 'bad request') {
                $qoute = $this->getQuote();
                $this->getCheckoutSession()->restoreQuote($qoute);
                $qoute->setIsActive(true);
                $order->cancel();
                $order->save();
                $url = $this->getTapHelper()->getUrl('checkout/cart');
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setUrl($url);
                $this->messageManager->addError(__("Transaction Failed."." Please check payment method and currency"));
                return $resultRedirect;
            }
            $this->addOrderHistory($order,'<br/>The customer was redirected to Tap');
        }
        return $this->chargeRedirect($charge_url);
    }

    public function chargeRedirect($url){
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($url);
        return $resultRedirect;
    }

}