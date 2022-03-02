<?php
namespace Gateway\Tap\Model\Config\Source;
class UImode implements \Magento\Framework\Option\ArrayInterface
{
 	public function toOptionArray()
 	{
  		return [
    		['value' => 'redirect', 'label' => __('Redirect')],
    		['value' => 'popup', 'label' => __('PopUp')],
    		['value' => 'token', 'label' =>__('Tokenization')],
    		
  		];
 	}
}