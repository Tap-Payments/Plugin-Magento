<?php

namespace Gateway\Tap\Controller\Standard;

class Redirect extends \Gateway\Tap\Controller\Tap
{
    public function execute()
    {
		
        if (isset($_GET['token'])) {
			//$amount = $_GET['amount'];
            $source_id = $_GET['token'];
        }
        else if (isset($_GET['knet'])) {
            $source_id = 'src_kw.knet';
        }
        else if (isset($_GET['benefit'])) {
            $source_id = 'src_bh.benefit';
        }
		//echo $source_id;exit;
        //echo $_GET['token'];exit;
        //echo $source_id;exit;
        // $source_id = null;
        // switch ($_GET) {
        //     case $_GET['token']:
        //         $source_id = $_GET['token'];
        //         break;
        //     case $_GET['knet']:
        //         $source_id = 'src_kw.knet';
        //     break;
        //     case $_GET['benefit']:
        //         $source_id = 'src_bh.benefit';
        //     break;
        // }

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
       // var_dump($_REQUEST);exit;
        // $_REQUEST['token'];exit;
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($url);
        return $resultRedirect;
        
    }

}