<?php

namespace OuterEdge\Filter\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_CONFIG_MULTIPLE_FILTER_ACTIVE = 'filter/product_list/enable_multiple_filter';
    
    const XML_PATH_CONFIG_AJAX_LOAD_ACTIVE = 'filter/product_list/enable_ajax_load';
    
    /**
     * Check whether multiple filters are active
     * 
     * @return boolean
     */
    public function isMultipleFilterActive()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CONFIG_MULTIPLE_FILTER_ACTIVE,
            ScopeInterface::SCOPE_STORE
        );
    }
    
    /**
     * Check whether ajax loading is active
     * 
     * @return boolean
     */
    public function isAjaxLoadingActive()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CONFIG_AJAX_LOAD_ACTIVE,
            ScopeInterface::SCOPE_STORE
        );
    }
}