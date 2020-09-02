<?php

namespace Tap\Knet\Controller\Standard;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Api\Data\CreditmemoInterface;
// use Magento\Sales\Model\Service\InvoiceService;
use Magento\Customer\Model\Session;
class Response extends \Tap\Knet\Controller\Knet
{
	 // public function __construct(\Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder){
	 // 	$this->transactionBuilder = $transactionBuilder;

	 // }


	public function createTransaction($order , $paymentData = array() )
	{
        try {
            //get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData);
            $payment->setTransactionId($paymentData);
            $payment->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
            );
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );
 
            $message = __('The authorized amount is %1.', $formatedPrice);
            //get the object of builder class
            //$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			//$transactionBuilder = $objectManager->get('\Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface');
            //$trans = $transactionBuilder;

            $transaction = $trans->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($paymentData)
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
            )
            ->setFailSafe(true)
            //build method creates the transaction and returns the object
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
 
            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );
            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();
 
            //return  $transaction->save()->getTransactionId();
        } catch (Exception $e) {
            //log errors here
        }
	}




	public function execute()
	{
		$debug_mode =  $this->getKnetHelper()->getConfiguration('payment/tap/debug');
		if ($debug_mode == 1)
			$live_secret_key = $this->getKnetHelper()->getConfiguration('payment/tap/test_secret_key');
		else {
			$live_secret_key = $this->getKnetHelper()-> getConfiguration('payment/tap/live_secret_key');
		}

		$returnUrl = $this->getKnetHelper()->getUrl('checkout/onepage/success');
		$resultRedirect = $this->resultRedirectFactory->create();
		$ref = $_REQUEST['tap_id'];
		$transaction_mode = substr($ref, 0, 4);
		if ($transaction_mode == 'auth') {
			$curl_url = 'https://api.tap.company/v2/authorize/';
		}
		else  {
			$curl_url = 'https://api.tap.company/v2/charges/';
		}
		$comment 	= 	"";
		$successFlag= 	false;

			$curl = curl_init();

			curl_setopt_array($curl, array(
  						CURLOPT_URL => $curl_url.$ref,
  							CURLOPT_RETURNTRANSFER => true,
  							CURLOPT_ENCODING => "",
  							CURLOPT_MAXREDIRS => 10,
  							CURLOPT_TIMEOUT => 30,
  							CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  							CURLOPT_CUSTOMREQUEST => "GET",
  							CURLOPT_POSTFIELDS => "{}",
  							CURLOPT_HTTPHEADER => array(
    							"authorization: Bearer $live_secret_key"
  							),
						)
			);

			$response = curl_exec($curl);
			//echo '<pre>';var_dump($response->reference);exit;
			$err = curl_error($curl);
			curl_close($curl);
			if ($err) {
  				echo "cURL Error #:" . $err;
			} 
			else {
				$response = json_decode($response);
				$payment_type = $response->source->payment_type;
				$charge_status = $response->status;
				if ($payment_type == 'CREDIT') {
  					$last_four = $response->card->last_four;
  					$payment_type = 'CREDIT CARD';
  				}
			}
			//var_dump($response->reference->order);exit;
			//$order = $this->getOrderbyId($response->reference->order);
			


			if ($charge_status == 'DECLINED'  ) {
				$returnUrl = $this->getTapHelper()->getUrl('checkout/onepage/failure');
	
				

				$qoute = $this->getQuote();
				$this->getCheckoutSession()->restoreQuote($qoute);
				$qoute->setIsActive(true);
				$order->cancel();
				$order->save();
				$qoute->setIsActive(true);
				$this->messageManager->addError(__("Transaction Failed"));
				
				return $resultRedirect->setUrl($returnUrl);
			}
		
  	

		if ($_REQUEST['tap_id'] && $transaction_mode !== 'auth' && $charge_status == 'CAPTURED' || $charge_status == 'INITIATED')
		{
			$order_idd = $response->reference->order;
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
  			$order_info = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($order_idd);
			
			//$order = getOrderbyId($order_idd);
			//var_dump($order_idd);exit;
			$payment = $order_info->getPayment();
			$reffer = $_REQUEST['tap_id'];
			$tid = '';
			//$transaction_id = $this->createTransaction($order ,$reffer );
				$orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
				$orderStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
				$order_info->setState($orderState)
                            ->setStatus($orderStatus)
                                ->addStatusHistoryComment("Tap Transaction Successful")
                                ->setIsCustomerNotified(true);

                // $payment->setTransactionId($tid);
                //         $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, true);
                        //$order->save();
				
			
				$objectManager2 = \Magento\Framework\App\ObjectManager::getInstance();
				$invioce = $objectManager2->get('\Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order_info);
				$invioce->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
				$invioce->register();
				
				$transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH, null, true, ""
    			);
            	$invioce->setTransactionId($reffer);
            	$invioce->save();


             	$payment->setTransactionId($reffer);
    			$payment->setParentTransactionId($payment->getTransactionId());
    			$transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH, null, true, ""
    			);
    			$transaction->setIsClosed(true);

    			$comment .=  '<br/><b>Tap payment successful</b><br/><br/>Tap ID - '.$_REQUEST['tap_id'].'<br/><br/>Order ID - '.$order_idd.'<br/><br/>Payment Type - Credit Card<br/><br/>Payment ID - '.$_REQUEST['tap_id'];

       //      	$payment->setTransactionId($ref);
    			// $payment->setParentTransactionId($payment->getTransactionId());
    			// $transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH, null, true, ""
    			// );
    			//$transaction->setIsClosed(true);
			
			$returnUrl = $this->getKnetHelper()->getUrl('checkout/onepage/success');
		}
		else if ($_REQUEST['tap_id'] && $transaction_mode == 'auth' ) 
		{
			$comment .=  '<br/><b>Tap payment successful</b><br/><br/>Tap ID - '.$_REQUEST['tap_id'].'<br/><br/>Order ID - '.$orderId.'<br/><br/>Payment Type - Credit Card<br/><br/>Payment ID - '.$_REQUEST['tap_id'];
			$order->setStatus($order::STATE_PAYMENT_REVIEW);
			$transaction_id = $this->createTransaction($order , $_REQUEST['tap_id']);
			$transaction = $order->setTransactionId($_REQUEST['tap_id']);
			$transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH, null, true, ""
					);
			$transaction->save();
			$transaction->setIsClosed(false);

		}
		else if ($charge_status !== 'CAPTURED' )
		{
			$errorMsg = 'It seems some issue in card authentication. Transaction Failed.';
			$order->setStatus($order::STATE_PENDING_PAYMENT);
			$comment = $errorMsg;
		}
		$this->addOrderHistory($order_info,$comment);
  		$order_info->save();
		return $resultRedirect->setUrl($returnUrl);
	}
}
