<?php 
namespace Gateway\Tap\Observer; 
use Magento\Framework\Event\ObserverInterface; 
use \Magento\Checkout\Model\Session as CheckoutSession;
 
class InventoryRevert implements ObserverInterface { 
 
 	protected $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession) {
        $this->checkoutSession = $checkoutSession;
    }

 
    public function execute(\Magento\Framework\Event\Observer $observer) { 
    		$order = $observer->getEvent()->getOrder();
    		$quote = $this->checkoutSession->getQuote();
    	  	if($order->getState() == 'canceled'){
    			$quote->setInventoryProcessed(true);
    		}
    		return;
      	}
}
