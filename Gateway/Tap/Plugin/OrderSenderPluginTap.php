<?php

namespace Gateway\Tap\Plugin;
use Magento\Sales\Model\Order;
class OrderSenderPluginTap {
    public function aroundSend( \Magento\Sales\Model\Order\Email\Sender\OrderSender $subject, callable $proceed, Order $order, $forceSyncMode = false ) {
        $payment = $order->getPayment()->getMethodInstance()->getCode();
        if ( $payment === 'tap' && $order->getState() !== 'processing') {
            return false;
        }
        return $proceed( $order, $forceSyncMode );
    }
}