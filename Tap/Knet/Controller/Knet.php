<?php

namespace Tap\Knet\Controller;
 
abstract class Knet extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Tap\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote = false;

    protected $_knetModel;

    protected $_knetHelper;
    
    protected $_orderHistoryFactory;

    protected $_invoiceService;


    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Gateway\tap\Model\tap $knetModel
     * @param \Gateway\tap\Helper\tap $knetHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Tap\Knet\Model\Knet $knetModel,
        \Tap\Knet\Helper\Data $knetHelper,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
    ) {
        
        parent::__construct($context);
        $this->_invoiceService = $invoiceService;
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_knetModel = $knetModel;
        $this->_knetHelper = $knetHelper;
        $this->_orderHistoryFactory = $orderHistoryFactory;
        $this->transaction        = $transaction;
        $this->transactionBuilder        = $transactionBuilder;

    }



    // *
    //  * Cancel order, return quote to customer
    //  *
    //  * @param string $errorMsg
    //  * @return false|string
     
    protected function _cancelPayment($errorMsg = '')
    {
        $gotoSection = false;
        $this->_knetHelper->cancelCurrentOrder($errorMsg);
        if ($this->_checkoutSession->restoreQuote()) {
            //Redirect to payment step
            $gotoSection = 'paymentMethod';
        }

        return $gotoSection;
    }


    protected function refund() {
        exit;
    }
    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        
        return $this->_orderFactory->create()->loadByIncrementId(
            $this->_checkoutSession->getLastRealOrderId()
        );
    }

    protected function addOrderHistory($order,$comment){
        $history = $this->_orderHistoryFactory->create()
            ->setComment($comment)
            ->setEntityName('order')
            ->setOrder($order);
            $history->save();
        return true;
    }
    
    protected function getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->getCheckoutSession()->getQuote();
        }
        return $this->_quote;
    }

    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    protected function getCustomerSession()
    {
        return $this->_customerSession;
    }

    protected function getKnetModel()
    {
        return $this->_knetModel;
    }

    protected function getKnetHelper()
    {
        return $this->_knetHelper;
    }
}