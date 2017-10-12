<?php

namespace OuterEdge\MultipleFilter\Model\Layer\Filter;

use Magento\CatalogSearch\Model\Layer\Filter\Price as FilterPrice;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\ResourceModel\Layer\Filter\Price as ResourceModelPrice;
use Magento\Customer\Model\Session;
use Magento\Framework\Search\Dynamic\Algorithm;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory;
use Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory;
use Magento\Framework\App\RequestInterface;

class Price extends FilterPrice
{
    /**
     * Price filter item factory
     *
     * @var PriceItemFactory
     */
    protected $priceFilterItemFactory;

    /**
     * @param ItemFactory $filterItemFactory
     * @param StoreManagerInterface $storeManager
     * @param Layer $layer
     * @param DataBuilder $itemDataBuilder
     * @param ResourceModelPrice $resource
     * @param Session $customerSession
     * @param Algorithm $priceAlgorithm
     * @param PriceCurrencyInterface $priceCurrency
     * @param AlgorithmFactory $algorithmFactory
     * @param PriceFactory $dataProviderFactory
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        ResourceModelPrice $resource,
        Session $customerSession,
        Algorithm $priceAlgorithm,
        PriceCurrencyInterface $priceCurrency,
        AlgorithmFactory $algorithmFactory,
        PriceFactory $dataProviderFactory,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $resource,
            $customerSession,
            $priceAlgorithm,
            $priceCurrency,
            $algorithmFactory,
            $dataProviderFactory,
            $data
        );
        $this->dataProvider = $dataProviderFactory->create(['layer' => $this->getLayer()]);
    }

    /**
     * Apply price range filter
     *
     * @param RequestInterface $request
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function apply(RequestInterface $request)
    {
        $filters = $request->getParam($this->getRequestVar(), []);
        if (empty($filters)) {
            return $this;
        }
        if (!is_array($filters)) {
            $filters = [$filters];
        }

        $priceFilters = [];
        foreach ($filters as $filter) {
            if (!$filter || is_array($filter)) {
                continue;
            }
            $filterParams = explode(',', $filter);
            $filter = $this->dataProvider->validateFilter($filterParams[0]);
            if (!$filter) {
                continue;
            }

            $this->dataProvider->setInterval($filter);
            $priorFilters = $this->dataProvider->getPriorFilters($filterParams);
            if ($priorFilters) {
                $this->dataProvider->setPriorIntervals($priorFilters);
            }

            list($from, $to) = $filter;
            $priceFilter = ['from' => $from, 'to' => empty($to) || $from == $to ? $to : $to - self::PRICE_DELTA];
            if (!strlen($priceFilter['to'])) {
                unset($priceFilter['to']);
            }
            $priceFilters[] = $priceFilter;

            $this->getLayer()->getState()->addFilter(
                $this->_createItem($this->_renderRangeLabel(empty($from) ? 0 : $from, $to), $filter)
            );
        }

        if (!empty($priceFilters)) {
            $collection = $this->getLayer()->getProductCollection();
            $connection = $collection->getConnection();

            $where = [
                'e.price'                 => [],
                'price_index.final_price' => [],
                'price_index.min_price'   => [],
                'price_index.max_price'   => []
            ];

            foreach ($priceFilters as $priceFilter) {
                foreach ($where as $attribute => &$conditions) {
                    $conditions[] = implode(' AND ', [
                        $connection->quoteInto("{$attribute} >= ?", $priceFilter['from']),
                        $connection->quoteInto("{$attribute} <= ?", $priceFilter['to']),
                    ]);
                }
            }

            $whereGroups = [];
            foreach ($where as $conditions) {
                $whereGroups[] = '(' . implode(') OR (', $conditions) . ')';
            }
            $whereString = '((' . implode(') OR (', $whereGroups) . '))';

            $this->getLayer()->getProductCollection()->getSelect()->where($whereString);
        }

        return $this;
    }

    /**
     * Get data array for building attribute filter items
     * Gets fresh copy of the product collection to ensure filter items don't change when selected
     *
     * @return array
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getItemsData()
    {
        $attribute = $this->getAttributeModel();
        $this->_requestVar = $attribute->getAttributeCode();

        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getCollectionProvider()->getCollection($this->getLayer()->getCurrentCategory());
        $this->getLayer()->prepareProductCollection($productCollection);

        $facets = $productCollection->getFacetedData($attribute->getAttributeCode());

        $data = [];
        if (count($facets) > 1) { // two range minimum
            foreach ($facets as $key => $aggregation) {
                $count = $aggregation['count'];
                if (strpos($key, '_') === false) {
                    continue;
                }
                $data[] = $this->prepareData($key, $count, $data);
            }
        }

        return $data;
    }

    /**
     * @param string $key
     * @param int $count
     * @return array
     */
    private function prepareData($key, $count)
    {
        list($from, $to) = explode('_', $key);
        if ($from == '*') {
            $from = $this->getFrom($to);
        }
        if ($to == '*') {
            $to = $this->getTo($to);
        }
        $label = $this->_renderRangeLabel(
            empty($from) ? 0 : $from * $this->getCurrencyRate(),
            empty($to) ? $to : $to * $this->getCurrencyRate()
        );
        $value = $from . '-' . $to;

        $data = [
            'label' => $label,
            'value' => $value,
            'count' => $count,
            'from' => $from,
            'to' => $to,
        ];

        return $data;
    }
}
