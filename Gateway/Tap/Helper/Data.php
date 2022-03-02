<?php

namespace Gateway\Tap\Helper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
class Data extends AbstractHelper
{
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
