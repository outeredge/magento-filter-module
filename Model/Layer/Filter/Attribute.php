<?php

namespace OuterEdge\MultipleFilter\Model\Layer\Filter;

use Magento\CatalogSearch\Model\Layer\Filter\Attribute as FilterAttribute;
use Magento\Framework\Filter\StripTags;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;

class Attribute extends FilterAttribute
{
    /**
     * @var StripTags
     */
    private $tagFilter;

    /**
     * @param ItemFactory $filterItemFactory
     * @param StoreManagerInterface $storeManager
     * @param Layer $layer
     * @param DataBuilder $itemDataBuilder
     * @param StripTags $tagFilter
     * @param array $data
     */
    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        StripTags $tagFilter,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $tagFilter,
            $data
        );
        $this->tagFilter = $tagFilter;
    }

    /**
     * Apply attribute option filter to product collection
     * Does not set $this->_items to empty array
     *
     * @param RequestInterface $request
     * @return $this
     * @throws LocalizedException
     */
    public function apply(RequestInterface $request)
    {
        $attributeValues = $request->getParam($this->_requestVar, []);
        if (empty($attributeValues)) {
            return $this;
        }
        if (!is_array($attributeValues)) {
            $attributeValues = [$attributeValues];
        }

        $attribute = $this->getAttributeModel();
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()
            ->getProductCollection();
        $productCollection->addFieldToFilter($attribute->getAttributeCode(), ['in' => $attributeValues]);

        foreach ($attributeValues as $attributeValue) {
            $label = $this->getOptionText($attributeValue);
            $this->getLayer()
                ->getState()
                ->addFilter($this->_createItem($label, $attributeValue));
        }

        return $this;
    }

    /**
     * Get data array for building attribute filter items
     * Gets fresh copy of the product collection to ensure filter items don't change when selected
     *
     * @return array
     * @throws LocalizedException
     */
    protected function _getItemsData()
    {
        $attribute = $this->getAttributeModel();

        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getCollectionProvider()
            ->getCollection($this->getLayer()->getCurrentCategory());
        $this->getLayer()->prepareProductCollection($productCollection);

        $optionsFacetedData = $productCollection->getFacetedData($attribute->getAttributeCode());

        if (count($optionsFacetedData) === 0
            && $this->getAttributeIsFilterable($attribute) !== static::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS
        ) {
            return $this->itemDataBuilder->build();
        }

        $options = $attribute->getFrontend()
            ->getSelectOptions();
        foreach ($options as $option) {
            if (empty($option['value'])) {
                continue;
            }

            $value = $option['value'];

            $count = isset($optionsFacetedData[$value]['count'])
                ? (int)$optionsFacetedData[$value]['count']
                : 0;
            // Check filter type
            if ($this->getAttributeIsFilterable($attribute) === static::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS
                && $count === 0
            ) {
                continue;
            }
            $this->itemDataBuilder->addItemData(
                $this->tagFilter->filter($option['label']),
                $value,
                $count
            );
        }

        return $this->itemDataBuilder->build();
    }
}
