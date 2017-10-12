<?php

namespace OuterEdge\MultipleFilter\Model\Layer;

use Magento\Catalog\Model\Layer\Category as LayerCategory;

class Category extends LayerCategory
{
    public function getCollectionProvider()
    {
        return $this->collectionProvider;
    }
}