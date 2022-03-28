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
        // $debug_mode =  $this->_tapHelper->getConfiguration('payment/tap/debug');
        // if ($debug_mode == 1) {
        //    $live_secret_key = $this->_tapHelper->getConfiguration('test_secret_key'); 
        // }
        // else {
        //     $live_secret_key = $this->_tapHelper->getConfiguration('live_secret_key');
        // }

        $response = ['success' => false];
        $body = $this->request->getBodyParams();
        $tap_id = $body['id'];
        //echo $tap_id;exit;
        $orderIncrementId = $body['reference']['order'];
        $headersAll = getallheaders();
        $this->logger->info(json_encode($headersAll));
        $this->logger->info(json_encode($body));
        
        //var_dump($headersAll['Hashstring']);
        
        $gateway_id = $body['reference']['gateway'];
        $amount = $body['amount'];
       // $amount = number_format((float)$amount, 3, '.', '');
      
    //     $currency = $body['currency'];
    //     $payment_reference = $body['reference']['payment'];
    //     $status = $body['status'];
    //     $created = $body['transaction']['created'];
    //     $tap_id = $body['id'];
    //     $ref = 'txn_0001';
    //     $active_sk = 'sk_test_kovrMB0mupFJXfNZWx6Etg5y';
    //     $active_pk = 'pk_test_Vlk842B1EA7tDN5QbrfGjYzh';
    //     $post_url =  'https://noonera.com/magento2/rest/V1/charge/post-api/';
    //     //$Hash = 'x_publickey'.$active_pk.'x_amount'.$final_amount.'x_currency'.$currency.'x_transaction'.$ref.'x_post'.$post_url;
    //     //$hashstring = hash_hmac('sha256', $Hash, $active_sk);
    //   $toBeHashedString = 'x_id'.$tap_id.'x_amont'.$amount.'x_currency'.$currency.'x_gateway_reference'.$gateway_id.'x_payment_reference'.$payment_reference.'x_status'.$status.'x_created'.$created.'';
    //     //echo $toBeHashedString.'<br>';
    // $myHashString = hash_hmac('sha256', $toBeHashedString, 'sk_test_kovrMB0mudfdpFJXfNZWx6Etg5y');
    //     //var_dump($myHashString);exit;
    //     print_r($myHashString);exit;
        $this->logger->info(json_encode($body));
        
       // echo $amount;exit;
   
        $objectManager2 = \Magento\Framework\App\ObjectManager::getInstance();

        $orderInterface = $objectManager2->create('Magento\Sales\Api\Data\OrderInterface');
        
        // $order = $this->orderRepository->get($incr);
        // $orderIncrementId = $order->getIncrementId();
        //echo $orderIncrementId;exit;
        $order_info = $orderInterface->loadByIncrementId($orderIncrementId);
        $stat = $order_info->getState();
            //echo $stat;exit;
        $payment = $order_info->getPayment();
        //echo $body['status'];exit;
        if ($body['status'] == 'CAPTURED' || $body['status'] == 'INITIATED' && $stat !== 'processing') {
            $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
            $orderStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
            $order_info->setState($orderState)
                            ->setStatus($orderStatus)
                                ->addStatusHistoryComment("Tap Transaction Successful-".$tap_id)
                                ->setIsCustomerNotified(true);
                                //$order_info->save();
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