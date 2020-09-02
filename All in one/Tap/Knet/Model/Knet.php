<?php

namespace Tap\Knet\Model;

use Tap\Knet\Helper\Data as DataHelper;
use Tap\Knet\Controller\Standard;

class Knet extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'knet';
    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_isOffline = false;
    protected $_canRefund = true;
    protected $_canCapture = true;
    protected $_canAuthorize = true;
    protected $helper;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = array('INR');
    protected $_formBlockType = 'Tap\Knet\Block\Form\Knet';
    //protected $_infoBlockType = 'Tap\Knet\Block\Info\Tap';
    protected $urlBuilder;


    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Tap\Knet\Helper\Data $helper,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Model\Order\Invoice $invoice,
        \Magento\Sales\Model\Service\CreditmemoService $creditmemoService,
        \Magento\Framework\App\Request\Http $request
      

    ) {
        $this->helper = $helper;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

        $this->_minAmount = "0.100";
        $this->_maxAmount = "1000000";
        $this->urlBuilder = $urlBuilder;
        $this->order = $order;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->invoice = $invoice;
        $this->request = $request;
       
    }

   public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote && (
                $quote->getBaseGrandTotal() < $this->_minAmount
                || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
        ) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $live_mode = $this->helper->getConfiguration('payment/knet/debug');
        //var_dump($live_mode);exit;
        if (!$live_mode)  {
            $live_secret_key = $this->helper->getConfiguration('payment/knet/live_secret_key');
        }
        else {
            $live_secret_key = $this->helper->getConfiguration('payment/knet/test_secret_key');
        }
     
        //$active_sk = 'sk_test_kovrMB0mupFJXfNZWx6Etg5y';
        
        $order_id = $this->request->getParam('order_id');
        $order = $this->order->load($order_id);
        $currency_code = $order->getOrderCurrencyCode();
        $transactionId = $payment->getParentTransactionId();
         //var_dump($transactionId);exit;
        $refund_request = [];
        $refund_request['charge_id']    =   $transactionId;
        $refund_request['amount']       =   $amount;
        $refund_request['currency']     =   $currency_code;
        $refund_request['description']  =   '';
        $refund_request['reason']       =   'Requested by customer';
        $refund_request['reference']['merchant']    =   'txn_0001';
        $refund_request['metadata']['udf1']         =   'test1';
        $refund_request['metadata']['udf1']         =   'test2'; 
        $refund_request['post']['url']              =   '127.0.0.1/magento';
        //$refund_request['']

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.tap.company/v2/refunds",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($refund_request),
            CURLOPT_HTTPHEADER => array(
                                    "authorization: Bearer ".$live_secret_key,
                                    "content-type: application/json"
                                ),
        ));


        $response = curl_exec($curl);
        //var_dump($response);exit;
        $obj = json_decode($response);
        $transactionId = $obj->id;
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }


        $payment
            ->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
            ->setParentTransactionId($transactionId)
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1);
            return $this;
        
    }

    public function knetRequest($order) {  
         // echo '<pre>';var_dump($order->getBillingAddress());exit;      
        //$mode = $this->getConfigData('debug');
        
        // if ($mode) {
        //     $active_sk = $this->getConfigData('test_secret_key');
        // }
        // else {
        //     $active_sk = $this->getConfigData('live_secret_key');
        // }
        // $transaction_mode   =   $this->getConfigData('transaction_mode');
        //$active_sk = $this->helper->active_sk();
       
        $test_mode = $this->helper->getConfiguration('payment/knet/debug');
        //var_dump($test_mode);exit;
        if ($test_mode) {
             $active_sk = $this->helper->getConfiguration('payment/knet/test_secret_key');
        }
        else {
            $active_sk =  $this->helper->getConfiguration('payment/knet/live_secret_key');
        }
        //var_dump($active_sk);exit;
        //$active_sk = 'sk_test_kovrMB0mupFJXfNZWx6Etg5y';
        $amount             =   $order->getGrandTotal();
        $currencyCode       =   'KWD';
        $orderid            =   $order->getEntityId();
        $CstFName           =   $order->getBillingAddress()->getFirstName();
        $CstLName           =   $order->getBillingAddress()->getLastName();
        $CstEmail           =   $order->getCustomerEmail();
        $CstMobile          =   $order->getBillingAddress()->getTelephone();
        $post_url           =   '';
        $redirectUrl        =   $this->urlBuilder->getUrl('knet/Standard/Response');
        //$redirectUrl = json_encode($redirectUrl);
        //echo json_encode($redirectUrl);exit;
        $trans_object = [];
        
        
        $request_url = "https://api.tap.company/v2/charges";
        $trans_object["amount"]                 = $amount;
        $trans_object["currency"]               = $currencyCode;
        $trans_object["threeDsecure"]           = true;
        $trans_object["save_card"]              = false;
        $trans_object["description"]            = 'Test Description';
        $trans_object["statement_descriptor"]   = 'Sample';
        $trans_object["metadata"]["udf1"]       = 'test';
        $trans_object["metadata"]["udf2"]          = 'test';
        $trans_object["reference"]["transaction"]  = 'txn_0001';
        $trans_object["reference"]["order"]        = $orderid;
        $trans_object["receipt"]["email"]          = false;
        $trans_object["receipt"]["sms"]            = true;
        $trans_object["customer"]["first_name"]    = $CstFName;
        $trans_object["customer"]["last_name"]    = $CstLName;
        $trans_object["customer"]["email"]        = $CstEmail;
        $trans_object["customer"]["phone"]["country_code"]       = '965';
        $trans_object["customer"]["phone"]["number"] = $CstMobile;
        $trans_object["source"]["id"] = 'src_kw.knet';
        $trans_object["source"]["lang"] = 'ar';
        $trans_object["post"]["url"] = 'http://127.0.0.1/magento2_2-1-0/knet/Standard/Redirect/';
        $trans_object["redirect"]["url"] = $redirectUrl;
        //echo '<pre>';var_dump($trans_object);exit;
        // $obj = json_encode($trans_object);
        // echo '<pre>';var_dump($obj);exit;

        // if ($transaction_mode == 'authorize') {
        //     $trans_object["auto"]["type"] = "VOID";
        //     $trans_object["auto"]["time"] = "100";
        //     $request_url = "https://api.tap.company/v2/authorize";
        // }

        $curl = curl_init();

        curl_setopt_array($curl, array(
                CURLOPT_URL => $request_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($trans_object),
                CURLOPT_HTTPHEADER => array(
                            "authorization: Bearer ".$active_sk,
                            "content-type: application/json",
                            "lang_code: ar"
                ),
            )
        );

        $response = curl_exec($curl);
        $response = json_decode($response);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            echo "cURL Error #:" . $err;
        } 
        else {
            return $response->transaction->url;
        }
    }
}