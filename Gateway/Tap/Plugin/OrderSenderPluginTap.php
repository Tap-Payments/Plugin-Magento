<?php

namespace Gateway\Tap\Plugin;

use Magento\Sales\Model\Order;

class OrderSenderPluginTap {
    public function aroundSend( \Magento\Sales\Model\Order\Email\Sender\OrderSender $subject, callable $proceed, Order $order, $forceSyncMode = true ) {
        $payment = $order->getPayment()->getMethodInstance()->getCode();
        //var_dump($order->getState());exit;
        
        if ( $payment === 'tap' && $order->getState() !== 'processing') {
            return false;
        }

        return $proceed( $order, $forceSyncMode );
    }
}