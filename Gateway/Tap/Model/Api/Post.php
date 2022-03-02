<?php
 
namespace Gateway\Tap\Model\Api;
use Magento\Payment\Helper\Data as PaymentHelper;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Api\Data\CreditmemoInterface;
 
class Post 
{
    protected $logger;

        
    private $historyFactory;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    private $historyRepository;

    private $config;
 
    public function __construct(
        LoggerInterface $logger,
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Status\HistoryFactory $historyFactory,
        \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $historyRepository,
        \Gateway\Tap\Helper\Data $tapHelper,
        \Gateway\Tap\Model\Tap $tapModel,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    )
    {
        $this->logger = $logger;
        $this->request = $request;
        $this->order = $order;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->historyFactory = $historyFactory;
        $this->historyRepository = $historyRepository;
        $this->_tapHelper = $tapHelper; 
        $this->_tapModel = $tapModel;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
    }
 
    /**
     * @inheritdoc
     */
 
    public function getPost()
    {
    try{
        $response = ['success' => false];
        $body = $this->request->getBodyParams();
        $tap_id = $body['id'];
        $orderIncrementId = $body['reference']['order'];
        $this->logger->info(json_encode($body));
        $objectManager2 = \Magento\Framework\App\ObjectManager::getInstance();
        $orderInterface = $objectManager2->create('Magento\Sales\Api\Data\OrderInterface');
        $order_info = $orderInterface->loadByIncrementId($orderIncrementId);
        $stat = $order_info->getState();
        $payment = $order_info->getPayment();
        if ($body['status'] == 'CAPTURED' || $body['status'] == 'INITIATED' && $stat !== 'processing') {
            $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
            $orderStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
            $order_info->setState($orderState)
                            ->setStatus($orderStatus)
                                ->addStatusHistoryComment("Tap Transaction Successful-".$tap_id)
                                ->setIsCustomerNotified(true);
            if ($order_info->getInvoiceCollection()->count() == 0) {
                $objectManager2 = \Magento\Framework\App\ObjectManager::getInstance();
                $invioce = $objectManager2->get('\Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order_info);
                $invioce->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invioce->register();
                $invioce->setTransactionId($tap_id);
                $invioce->save();
                $transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH, null, true, ""
                );
                $payment->setTransactionId($tap_id);
                $payment->setParentTransactionId($payment->getTransactionId());
                $transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH, null, true, ""
                );
                $transaction->setIsClosed(true);
            }
            $order_info->save();
        }
    }
                
     catch (\Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
            $this->logger->info($e->getMessage());
        }
    }
}