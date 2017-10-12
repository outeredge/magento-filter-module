<?php

namespace OuterEdge\MultipleFilter\Model\ResourceModel\Fulltext;

use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection as FulltextCollection;

class Collection extends FulltextCollection
{
    /**
     * Resets the _totalRecords to ensure the toolbar amount is calculated correctly after applying custom filters
     *
     * @inheritdoc
     */
    protected function _renderFiltersBefore()
    {
        $result = parent::_renderFiltersBefore();
        $this->_totalRecords = null;
        return $result;
    }
}
