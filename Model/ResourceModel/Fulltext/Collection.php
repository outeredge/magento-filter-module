<?php

namespace OuterEdge\Filter\Model\ResourceModel\Fulltext;

use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection as FulltextCollection;
use OuterEdge\Filter\Helper\Data as Helper;
use Magento\Store\Model\ScopeInterface;

class Collection extends FulltextCollection
{
    /**
     * Resets the _totalRecords to ensure the toolbar amount is calculated correctly after applying custom filters
     *
     * @inheritdoc
     */
    protected function _renderFiltersBefore()
    {
        $isMultipleFilterActive = $this->_scopeConfig->getValue(
            Helper::XML_PATH_CONFIG_MULTIPLE_FILTER_ACTIVE,
            ScopeInterface::SCOPE_STORE
        );
        if (!$isMultipleFilterActive) {
            return parent::_renderFiltersBefore();
        }
        
        $result = parent::_renderFiltersBefore();
        $this->_totalRecords = null;
        return $result;
    }
}
