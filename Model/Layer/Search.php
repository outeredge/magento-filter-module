<?php

namespace OuterEdge\Filter\Model\Layer;

use Magento\Catalog\Model\Layer\Search as LayerSearch;

class Search extends LayerSearch
{
    public function getCollectionProvider()
    {
        return $this->collectionProvider;
    }
}

