<?php

namespace Gateway\Tap\Controller\Standard;

class Redirect extends \Gateway\Tap\Controller\Tap
{
    public function execute()
    {
        if (isset($_GET['token'])) {
            $source_id = $_GET['token'];
        }
        else if (isset($_GET['knet'])) {
            $source_id = 'src_kw.knet';
        }
        else if (isset($_GET['benefit'])) {
            $source_id = 'src_bh.benefit';
        }
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
        $charge_url = $this->getTapModel()->redirectMode($order,$source_id);
        if ($order->getBillingAddress())
        {
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