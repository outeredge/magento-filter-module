<?php

namespace OuterEdge\Filter\Model\Layer\Filter;

use Magento\CatalogSearch\Model\Layer\Filter\Category as FilterCategory;
use Magento\Catalog\Model\Layer\Filter\DataProvider\Category as CategoryDataProvider;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Framework\Escaper;
use Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory as CategoryDataProviderFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\RequestInterface;

class Category extends FilterCategory
{
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var CategoryDataProvider
     */
    private $dataProvider;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @param ItemFactory $filterItemFactory
     * @param StoreManagerInterface $storeManager
     * @param Layer $layer
     * @param DataBuilder $itemDataBuilder
     * @param Escaper $escaper
     * @param CategoryDataProviderFactory $categoryDataProviderFactory
     * @param CategoryFactory $categoryFactory
     * @param array $data
     */
    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        Escaper $escaper,
        CategoryDataProviderFactory $categoryDataProviderFactory,
        CategoryFactory $categoryFactory,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $escaper,
            $categoryDataProviderFactory,
            $data
        );
        $this->escaper = $escaper;
        $this->dataProvider = $categoryDataProviderFactory->create(['layer' => $this->getLayer()]);
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * Apply category filter to product collection
     * Always add the main category as root category
     * Set product ids filter from sub categories
     *
     * @param   RequestInterface $request
     * @return  $this
     */
    public function apply(RequestInterface $request)
    {
        $mainCategoryId = $request->getParam('id');
        $this->dataProvider->setCategoryId($mainCategoryId);
        $mainCategory = $this->dataProvider->getCategory();
        $this->getLayer()->getProductCollection()->addCategoryFilter($mainCategory);

        $categoryIds = $request->getParam($this->_requestVar, []);
        if (empty($categoryIds)) {
            return $this;
        }
        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }

        $categoryFilterProductIds = [];
        foreach ($categoryIds as $categoryId) {
            $category = $this->categoryFactory->create()->load($categoryId);

            $categoryFilterProductIds = array_merge(
                $categoryFilterProductIds,
                $category->getProductCollection()->getColumnValues('entity_id')
            );

            if ($mainCategoryId != $categoryId) {
                $this->getLayer()->getState()->addFilter($this->_createItem($category->getName(), $categoryId));
            }
        }

        if (!empty($categoryFilterProductIds)) {
            $this->getLayer()->getProductCollection()
                ->addAttributeToFilter('entity_id', ['in' => $categoryFilterProductIds]);
        }

        return $this;
    }

    /**
     * Get data array for building category filter items
     * Gets fresh copy of the product collection to ensure filter items don't change when selected
     *
     * @return array
     */
    protected function _getItemsData()
    {
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getCollectionProvider()
            ->getCollection($this->getLayer()->getCurrentCategory());
        $optionsFacetedData = $productCollection->getFacetedData('category');

        $category = $this->dataProvider->getCategory();
        if ($category->getIsActive()) {
            $categories = $category->getChildrenCategories();
            foreach ($categories as $category) {
                if ($category->getIsActive()
                    && isset($optionsFacetedData[$category->getId()])
                ) {
                    $this->itemDataBuilder->addItemData(
                        $this->escaper->escapeHtml($category->getName()),
                        $category->getId(),
                        $optionsFacetedData[$category->getId()]['count']
                    );
                }
            }
        }
        return $this->itemDataBuilder->build();
    }
}
