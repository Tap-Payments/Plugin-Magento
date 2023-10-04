<?php
namespace Gateway\Tap\Model\Config\Source;

class Language implements \Magento\Framework\Option\ArrayInterface
{
 	public function toOptionArray()
 	{
  		return [
    		['value' => 'en', 'label' => __('English')],
    		['value' => 'ar', 'label' => __('Arabic')],
   		
  		];
 	}
}