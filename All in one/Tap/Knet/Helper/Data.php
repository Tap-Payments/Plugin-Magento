<?php

namespace Tap\Knet\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    public function active_sk() {
          
    }

    public function getUrl($route, $params = [])
	{
		return $this->_getUrl($route, $params);
	}

	public function getConfiguration($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }
    

}