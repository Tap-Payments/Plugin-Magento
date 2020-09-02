<?php

namespace Gateway\Tap\Controller\Standard;






class Response extends \Gateway\Tap\Controller\Tap
{


	public function createTransaction($order = null, $paymentData = array())
	{
		try {
			$payment = $order->getPayment();
			$invoice = $order->getInvoiceCollection();
			$payment->setLastTransId($paymentData['tap_id']);
			$payment->setTransactionId($paymentData['tap_id']);
			$payment->setAdditionalInformation(
				[\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
			);
			$formatedPrice = $order->getBaseCurrency()->formatTxt(
				$order->getGrandTotal()
			);
 
			$message = __('The authorized amount is %1.', $formatedPrice);
			$trans = $this->transactionBuilder;
			$transaction = $trans->setPayment($payment)
			->setOrder($order)
			->setTransactionId($paymentData['tap_id'])
			->setAdditionalInformation(
				[\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
			)
			->setFailSafe(true)
			->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

	
			$payment->addTransactionCommentsToOrder(
				$transaction,
				$message
			);
			$payment->setParentTransactionId(null);
			$payment->save();
			$order->save();
 
			return  $transaction->save()->getTransactionId();
		} catch (Exception $e) {

			//log errors here
		}
	}




	public function execute()
	{
		$resultRedirect = $this->resultRedirectFactory->create();
		$debug_mode =  $this->getTapHelper()->getConfiguration('payment/tap/debug');
		if ($debug_mode == 1)
			$live_secret_key = $this->getTapHelper()->getConfiguration('payment/tap/test_secret_key');
		else {
			$live_secret_key = $this->getTapHelper()-> getConfiguration('payment/tap/live_secret_key');
		}
		if (empty($_REQUEST['tap_id'])) {

			$returnUrl = $this->getTapHelper()->getUrl('checkout/onepage/failure');
			$this->messageManager->addError(__("Transaction unsccessful"));
			return $resultRedirect->setUrl($returnUrl);
		}
		$lastorderId = $this->_checkoutSession->getLastOrderId();
		$lastorderId = (int)$lastorderId;
		$order      =   $this->getOrderById($lastorderId);
		$orderId = $order->getEntityId();
		$payment = $order->getPayment();
		
		$order = $this->getOrder($orderId);
		$returnUrl = $this->getTapHelper()->getUrl('checkout/onepage/success');
		
		$ref = $_REQUEST['tap_id'];
		$transaction_mode = substr($ref, 0, 4);
		if ($transaction_mode == 'auth') {
			$curl_url = 'https://api.tap.company/v2/authorize/';
		}
		else  {
			$curl_url = 'https://api.tap.company/v2/charges/';
		}
		$order 		=	$this->getOrderById($orderId);
		$payment = $order->getPayment();
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

			$transaction_id = $this->createTransaction($order , $_REQUEST);
			
			$invioce = $this->_invoiceService->prepareInvoice($order);
			$invioce->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
			$invioce->register();
			$invioce->setState(\Magento\Sales\Model\Order\Invoice::STATE_PAID);
			$transaction = $invioce->setTransactionId($_REQUEST['tap_id']);
			$transaction->save();
			$invioce->save();
			$payment->setTransactionId($_REQUEST['tap_id']);
			$payment->setParentTransactionId($payment->getTransactionId());
			$transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, true, ""
				);
			$transaction->setIsClosed(true);


			$comment .=  '<br/><b>Tap payment successful</b><br/><br/>Tap ID - '.$_REQUEST['tap_id'].'<br/><br/>Order ID - '.$orderId.'<br/><br/>Payment Type - Credit Card<br/><br/>Payment ID - '.$_REQUEST['tap_id'];
			$order->setStatus($order::STATE_PROCESSING);
			$order->setExtOrderId($orderId);
			$returnUrl = $this->getTapHelper()->getUrl('checkout/onepage/success');
		}
		else if ($_REQUEST['tap_id'] && $transaction_mode == 'auth' ) 
		{
			$comment .=  '<br/><b>Tap payment successful</b><br/><br/>Tap ID - '.$_REQUEST['tap_id'].'<br/><br/>Order ID - '.$orderId.'<br/><br/>Payment Type - Credit Card<br/><br/>Payment ID - '.$_REQUEST['tap_id'];
			$order->setStatus($order::STATE_PAYMENT_REVIEW);
			$transaction_id = $this->createTransaction($order , $_REQUEST);
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
		$this->addOrderHistory($order,$comment);
		//$payment->setCcLast4($last_four);
		//$payment->setCcType($payment_type);
  		$order->save();
		return $resultRedirect->setUrl($returnUrl);
	}
}
