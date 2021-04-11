<?php

namespace Gateway\Tap\Block\Info;
use Magento\Sales\Model\Order;


class Tap extends \Magento\Payment\Block\Info
{
    protected $_template = 'Gateway_Tap::info/tap.phtml';
    protected $transactions;

	public function __constructor(
  	\Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactions
	)
{
  $this->transactions = $transactions;
}
   
	public function getChargeId($orderid) {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$order = $objectManager->create('Magento\Sales\Model\Order')->load($orderid);
		$transaction = $order->getPayment()->getData();
		$last = $order->getPayment()->getCcLast4();
		if (!empty($transaction['entity_id'] && !empty($transaction['additional_information']['tap_id']))) {
			$charge_id = $transaction['additional_information']['tap_id'];
			if ($charge_id) {
				return $charge_id;
			}
		}
	}

	public function getLastFourDigits($orderid) {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$order = $objectManager->create('Magento\Sales\Model\Order')->load($orderid);
		$last = $order->getPayment()->getCcLast4();
		return $last;
	}

	public function getPaymentType($orderid) {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$order = $objectManager->create('Magento\Sales\Model\Order')->load($orderid);
		//var_dump($order->getPayment()->getCcType());exit;
		return $order->getPayment()->getCcType();
	}
}
