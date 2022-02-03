<?php

class Tap_TapCheckout_Block_Shared_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $shared = $this->getOrder()->getPayment()->getMethodInstance();

        $form = new Varien_Data_Form();
        $form_field = $shared->getFormFields();
        $form->setAction($shared->getTapCheckoutSharedUrl())
            ->setId('tapcheckout_shared_checkout')
            ->setName('tapcheckout_shared_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);
          //  echo '<pre>' ;print_r($form_field);exit;
// echo '<pre>' ;print_r($form_field['ReturnURL']);exit;
                    $trans_object["amount"]                   = $form_field['ItemPrice1'];
                    $trans_object["currency"]                 = $form_field['CurrencyCode'];
                    $trans_object["threeDsecure"]             = true;
                    $trans_object["save_card"]                = false;
                    $trans_object["description"]              = "ORDER ID :".$form_field['OrdID'];
                    $trans_object["statement_descriptor"]     = 'Sample';
                    $trans_object["metadata"]["udf1"]          = 'test';
                    $trans_object["metadata"]["udf2"]          = 'test';
                    $trans_object["reference"]["transaction"]  = 'txn_0001';
                    $trans_object["reference"]["order"]        = $form_field['OrdID'];
                    $trans_object["receipt"]["email"]          = false;
                    $trans_object["receipt"]["sms"]            = true;
                    $trans_object["customer"]["first_name"]    = $form_field['CstFName'];
                    $trans_object["customer"]["last_name"]    = $form_field['CstLName'];
                    $trans_object["customer"]["email"]        = $form_field['CstEmail'];
                    $trans_object["customer"]["phone"]["country_code"]       = '';
                    $trans_object["customer"]["phone"]["number"] = $form_field['CstMobile'];
                    $trans_object["source"]["id"] = 'src_all';
                    $trans_object["post"]["url"] = $form_field['ReturnURL'];
                    $trans_object["redirect"]["url"] = $form_field['ReturnURL'];
                    $frequest = json_encode($trans_object);
                    $frequest = stripslashes($frequest);
                  //  var_dump($frequest);exit;
                      $curl = curl_init();
                       curl_setopt_array($curl, array(
                       CURLOPT_URL => "https://api.tap.company/v2/charges",
                       CURLOPT_RETURNTRANSFER => true,
                       CURLOPT_ENCODING => "",
                       CURLOPT_MAXREDIRS => 10,
                       CURLOPT_TIMEOUT => 30,
                       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                       CURLOPT_CUSTOMREQUEST => "POST",
                       CURLOPT_POSTFIELDS => $frequest,
                       CURLOPT_HTTPHEADER => array(
                        "authorization: Bearer ".$form_field['MEID'],
                        "content-type: application/json"
                    ),
                       ));

                     $response = curl_exec($curl);
                     $response = json_decode($response);
                   //  var_dump($response->transaction->url);exit;
                     Mage::app()->getResponse()->setRedirect($response->transaction->url) ->sendResponse();

return ;

        foreach ($shared->getFormFields() as $field=>$value) {
            $form->addField($field, 'text', array('name'=>$field, 'value'=>$value));

        }
  
        $html = '<html><body>';
        $html.= $this->__('</ br></ br></ br><p style="text-align:center">You will be redirected to Tap in a few seconds.</p>');
		$html.= $this->__('</ br><p style="text-align:center"><img src="https://www.gotapnow.com/web/tap.png"/></p>');
        $html.= $form->toHtml();

        $html.= '<script type="text/javascript">document.getElementById("tapcheckout_shared_checkout").submit();</script>';
        $html.= '</body></html>';


        return $html;

    }
}