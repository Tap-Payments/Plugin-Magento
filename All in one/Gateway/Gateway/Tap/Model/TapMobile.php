<?php
namespace Gateway\Tap\Model;
use Gateway\Tap\Api\MobileInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
use Gateway\Tap\Model;
use Gateway\Tap\Helper\Data as DataHelper;

class TapMobile implements MobileInterface
{
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Gateway\Tap\Helper\Data $helper       
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->helper = $helper;
    }
    /**
     * Returns order id and redirect url to user
     *
     * @api
     * @param token .
     * @return order id and redirect url.
     */
    public function mobile() {
        
        //         try {

        //     $client2 = new GuzzleHttpClient(); //GuzzleHttp\Client
        //     $apiRequest2 = $client2->request('GET', $baseurl.'/rest/default/V1/orders/3', [
        //         //'body' => $request,
        //         'headers' => [
        //             "Authorization" => "Bearer j28iwufjl0he290c4fshiqvblu95tm91",
        //             "Content-Type" => "application/json"
        //         ]
        //     ]);
        //     //var_dump($apiRequest2);exit;
        //     $response2 =  $apiRequest2->getStatusCode();
        //     $content2 = json_decode($apiRequest2->getBody());
        //     //var_dump($content2->base_subtotal);exit;
        //     $message2 = array(
        //         'subtotal' => $content2->base_subtotal,
        //         //'redirect_url' => $url
        //     );
        //     print_r($message2['subtotal']);exit;

        //     echo $this->response(200, $message);exit;
            
        // } catch (RequestException $re) {

        //     echo $this->response(200, ['error' => $re->getMessage()]);exit;
        // }
        $baseurl = $this->_storeManager->getStore()->getBaseUrl();
        $debug =  $this->_scopeConfig->getValue('payment/tap/debug', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $mid = $this->_scopeConfig->getValue('payment/tap/merchant_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if($debug == 1){
             $url = $this->helper->TAP_PAYMENT_URL_TEST;
         }else{
             $url = $this->helper->TAP_PAYMENT_URL_PROD;
         }
        $headers =  getallheaders();
        if (empty($headers['Authorization'])) {
            $error = [
                'error' => 'Authorization header is missing'
            ];
            echo $this->response(200, $error);exit;
        }
        $request = file_get_contents('php://input');
        //$jsonData = \Mage::helper('core')->jsonEncode($request->getData());
        //var_dump($jsonData);exit;
        $authorization = $headers['Authorization'];
        $token_raw = explode(' ', $authorization);
        $token = $token_raw[1];
        try {

            $client = new GuzzleHttpClient(); //GuzzleHttp\Client
            $apiRequest = $client->request('POST', $baseurl.'rest/default/V1/carts/mine/payment-information', [
                'body' => $request,
                'headers' => [
                    "Authorization" => "Bearer ".$token,
                    "Content-Type" => "application/json"
                ]
            ]);
            $response =  $apiRequest->getStatusCode();
            $content = json_decode($apiRequest->getBody());
            $message = array(
                'ID' => $content,
                'redirect_url' => $url
            );

            echo $this->response(200, $message);exit;
            
        } catch (RequestException $re) {

            echo $this->response(200, ['error' => $re->getMessage()]);exit;
        }
         

    }



    function response($status,$data)
    {
        header("HTTP/1.1 ".$status);     
        $json_response = json_encode($data, JSON_UNESCAPED_SLASHES);
        echo $json_response;
    }
}