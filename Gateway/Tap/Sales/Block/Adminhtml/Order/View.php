<?php
/**
 * MageVision Blog27
 *
 * @category     MageVision
 * @package      MageVision_Blog27
 * @author       MageVision Team
 * @copyright    Copyright (c) 2018 MageVision (https://www.magevision.com)
 * @license      http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Gateway\Tap\Sales\Block\Adminhtml\Order;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;
class View
{
    public function beforeSetLayout(OrderView $subject)
    {
        // $subject->addButton(
        //     'order_custom_button',
        //     [
        //         'label' => __('Refund'),
        //         'class' => __('save submit-button refund primary'),
        //         'id' => 'order-view-custom-button',
        //         'onclick' => 'setLocation(\'' . $subject->getUrl('hello();') . '\')'
        //     ]
        // );
    }
}