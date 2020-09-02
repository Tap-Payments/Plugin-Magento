<?php

namespace Tap\Knet\Controller\Standard;

class Response extends \Tap\Knet\Controller\Knet
{

	public function createTransaction($order = null, $paymentData = array())
	{
		try {
			$payment = $order->getPayment();
			//var_dump($payment);exit;
			$invoice = $order->getInvoiceCollection();
			//var_dump($paymentData);exit;
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
			$payment->setParentTransactionId($payment->getTransactionId());
			$transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, true, ""
				);
			$transaction->setIsClosed(true);
			$payment->save();
			$order->save();
 
			return  $transaction->save()->getTransactionId();
		} catch (Exception $e) {
			//log errors here
		}
	}
	public function execute()
	{
		$ref = $_REQUEST['tap_id'];
		//echo $ref;exit;
		$lastorderId = $this->_checkoutSession->getLastOrderId();
		//echo '<prer>';var_dump($lastorderId);exit;
		$lastorderId = (int)$lastorderId;
		$order      =   $this->getOrder($lastorderId);
		$resultRedirect = $this->resultRedirectFactory->create();
		$success_url = $this->getKnetHelper()->getUrl('checkout/onepage/success');
		$fail_url = $this->getKnetHelper()->getUrl('checkout/onepage/failure');
		//echo $res;exit;
		$curl_url = 'https://api.tap.company/v2/charges/';
		$live_mode = $this->getKnetHelper()->getConfiguration('payment/knet/debug');
		if (!$live_mode)  {
			$live_secret_key = $this->getKnetHelper()->getConfiguration('payment/knet/live_secret_key');
		}
		else {
			$live_secret_key = $this->getKnetHelper()->getConfiguration('payment/knet/test_secret_key');
		}
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
		$response = json_decode($response);
		var_dump($response);exit;
			if ($response) {
				$charge_status = $response->status;
				if ($charge_status == 'CAPTURED') {
					$transaction_id = $this->createTransaction($order , $_REQUEST);
					$order->setStatus($order::STATE_PROCESSING);
					$comment = 'Tap Payment Successful';
					$this->addOrderHistory($order,$comment);
					$invoice = $this->_invoiceService->prepareInvoice($order);
					$invoice->register();
					$invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
					$invoice->setState(\Magento\Sales\Model\Order\Invoice::STATE_PAID);
					/*$invoice->setBaseGrandTotal($this->amount);*/
					/*$invoice->register();
                    $invoice->getOrder()->setIsInProcess(true);*/
                    $invoice->pay();
					$transaction = $invoice->setTransactionId($_REQUEST['tap_id']);
					$transaction->save();
					$order->save();
					$invoice->save();
					$payment = $order->getPayment();
					$payment->setTransactionId($_REQUEST['tap_id']);
					$payment->setParentTransactionId($payment->getTransactionId());
					$transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, true, ""
					);
					$transaction->setIsClosed(true);
					
					
					

					$returnUrl = $success_url;
				}
				else if ($charge_status == 'FAILED'){
					
					$qoute = $this->getQuote();
					$this->getCheckoutSession()->restoreQuote($qoute);
					$qoute->setIsActive(true);
					$order->cancel();
					$order->save();
					$qoute->setIsActive(true);
					$this->messageManager->addError(__("Transaction Failed"));
					$returnUrl = $fail_url;
				}
				
			}
			else {
				$err = curl_error($curl);
				echo $err;	
			}
			curl_close($curl);
			return $resultRedirect->setUrl($returnUrl);
	}
}
?>