<?php

namespace Gateway\Tap\Controller\Standard;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Api\Data\CreditmemoInterface;
// use Magento\Sales\Model\Service\InvoiceService;
use Magento\Customer\Model\Session;
//use Magento\Framework\DB\Transaction;

class Response extends \Gateway\Tap\Controller\Tap
{

	 // public function __construct(\Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder){
	 // 	$this->transactionBuilder = $transactionBuilder;

	 // }
	public function createTransaction($order = null, $paymentData = array())
    {
        try {
            //get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['ref']);
            $payment->setTransactionId($paymentData['ref']);
            $payment->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
            );
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );
 
            $message = __('The authorized amount is %1.', $formatedPrice);
            //get the object of builder class
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$transactionBuilder = $objectManager->get('\Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface');
            $trans = $transactionBuilder;
            $transaction = $trans->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($paymentData['ref'])
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
 
            return  $transaction->save()->getTransactionId();
        } catch (Exception $e) {
            //log errors here
        }
    }
    public function execute()
    {
		$orderId	=	$_REQUEST['trackid'];
		//var_dump($orderID);exit;
		$ref = $_REQUEST['ref'];
		//echo $ref;exit;
		$order 		=	$this->getOrderById($orderId);
		$payment = $order->getPayment();
		//$payment->setTransactionId('12121212121')->setIsTransactionClosed(0);
		$comment 	= 	"";
		$successFlag= 	false;
		
        if(isset($_REQUEST['result']))
        {
			if($_REQUEST['result']=='SUCCESS')
			{
				$transaction_id = $this->createTransaction($order , $_REQUEST);

				$objectManager2 = \Magento\Framework\App\ObjectManager::getInstance();
				$invioce = $objectManager2->get('\Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order);
				$invioce->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
				$invioce->register();
				
				$invioce->setState(\Magento\Sales\Model\Order\Invoice::STATE_PAID);
            	$invioce->setTransactionId($ref);
            	$invioce->save();

            	$payment->setTransactionId($ref);
    			$payment->setParentTransactionId($payment->getTransactionId());
    			$transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH, null, true, ""
    			);
    			$transaction->setIsClosed(true);

            	// $objectManager4 = \Magento\Sales\Api\Data\ObjectManager::getInstance();
            	// $creditMemo = $objectManager4->set($offlineRequested = false);
            	
    //         	$objectManager3 = \Magento\Framework\App\ObjectManager::getInstance();
    // //         	$payment->setTransactionId($ref);
    // // 			$payment->setParentTransactionId($payment->getTransactionId());
    // // 			$transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, true, ""
    // // 			);
    // // $transaction->setIsClosed(true);
    //         	$transactionSave = $objectManager3->get('\Magento\Framework\DB\Transaction')->addObject($invioce)->addObject(
    //             	$invioce->getOrder()
    //             );
    // //             //var_dump($transactionSave);exit;
    //              $transactionSave->save();
    //              //$this->invoiceSender->send($invoice);
    // //         	//send notification code
    //         	$order->addStatusHistoryComment(
    //             	__('Notified customer about invoice #%1.', $invioce->getId())
    //         	)
    //         ->setIsCustomerNotified(true)
    //         ->save();
				$params = $this->getRequest()->getParams();		
				//var_dump($params);exit;	
				if($this->getTapModel()->validateResponse($params))
				{

					$successFlag = true;
					$comment .=  '<br/><b>Tap payment successful</b><br/><br/>Tap ID - '.$_REQUEST['ref'].'<br/><br/>Order ID - '.$_REQUEST['trackid'].'<br/><br/>Payment Type - '.$_REQUEST['crdtype'].'<br/><br/>Payment ID - '.$_REQUEST['payid'];
					$order->setStatus($order::STATE_PROCESSING);
					$order->setExtOrderId($orderId);
					$returnUrl = $this->getTapHelper()->getUrl('checkout/onepage/success');
				}
				else
				{
					$errorMsg = 'It seems some issue in server to server communication. Kindly connect with administrator.';
					$comment .=  '<br/>Hash string Mismatch / Fraud Deducted<br/><br/>Tap ID - '.$_REQUEST['ref'].'<br/><br/>Order ID - '.$_REQUEST['trackid'];
					$order->setStatus($order::STATE_PAYMENT_REVIEW);
					$returnUrl = $this->getTapHelper()->getUrl('checkout/onepage/failure');
				}
			}
			else
			{
				if($_REQUEST['result']=='FAILURE' || $_REQUEST['result']=='CANCELLED')
				{
					$errorMsg = 'Tap Transaction Failed ! Transaction was cancelled.';
					$comment .=  "Payment cancelled by user";
					$order->setStatus($order::STATE_CANCELED);
					$this->_cancelPayment("Payment cancelled by user");
					//$order->save();
					$returnUrl = $this->getTapHelper()->getUrl('checkout/cart');
				}
				else
				{
					$errorMsg = 'Tap Transaction Failed !';
					$comment .=  "Failed";
					$order->setStatus($order::STATE_PAYMENT_REVIEW);
					$returnUrl = $this->getTapHelper()->getUrl('checkout/onepage/failure');
				}
			}            
        }
        else
        {
			$errorMsg = 'Tap Transaction Failed ! Fraud has been detected';
			$comment .=  "Fraud Deducted";
            $order->setStatus($order::STATUS_FRAUD);
            $returnUrl = $this->getTapHelper()->getUrl('checkout/onepage/failure');
        }
		$this->addOrderHistory($order,$comment);
        $order->save();
		if($successFlag)
		{
			$this->messageManager->addSuccess( __('Tap transaction has been successful.') );
		}
		else
		{
			$this->messageManager->addError( __($errorMsg) );
		}
        $this->getResponse()->setRedirect($returnUrl);
    }

}
