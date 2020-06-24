<?php

namespace Gateway\Tap\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\UrlInterface as UrlInterface;
use Gateway\Tap\Helper;
use Magento\Framework\App\Config\ScopeConfigInterface;

class TapConfigProvider implements ConfigProviderInterface
{
    protected $methodCode = "tap";

    protected $method;
    
    protected $urlBuilder;
    protected $checkoutSession;

    public function __construct(PaymentHelper $paymentHelper, UrlInterface $urlBuilder) {
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->urlBuilder = $urlBuilder;
    }



    public function getConfig()
    {
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->get('Magento\Sales\Model\Order');
        $checkoutSession = $this->checkoutSession;
        $test_public_key = $this->method->getConfigData('test_public_key');
        $live_public_key = $this->method->getConfigData('live_public_key');
        $post_url = $this->method->getConfigData('post_url');
        $mode = $this->method->getConfigData('debug');
        $uimode = $this->method->getConfigData('ui_mode');
        $save_card = $this->method->getConfigData('save_card');
        if ($mode) {
            $active_pk = $test_public_key;
        }
        else {
            $active_pk = $live_public_key;
        }
        $transaction_mode = $this->method->getConfigData('transaction_mode');

       

        return $this->method->isAvailable() ? [
            'payment' => [
                'tap' => [
                    'responseUrl' => $this->urlBuilder->getUrl('tap/Standard/Response', ['_secure' => true]),
                    'redirectUrl' => $this->urlBuilder->getUrl('tap/Standard/Redirect'),
                    'active_pk' => $active_pk,
                    'transaction_mode' => $transaction_mode,
                    'post_url' => $post_url,
                    'uimode' => $uimode,
                    'save_card' => $save_card
                ]
            ],
         
        ] : [];
    }

}
