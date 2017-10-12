<?php

namespace OuterEdge\MultipleFilter\Model\Layer\Filter;

use Magento\Catalog\Model\Layer\Filter\Item as FilterItem;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Theme\Block\Html\Pager;

class Item extends FilterItem
{
    /**
     * @param RequestInterface $request
     * @param UrlInterface $url
     * @param Pager $htmlPagerBlock
     * @param array $data
     */
    public function __construct(
        RequestInterface $request,
        UrlInterface $url,
        Pager $htmlPagerBlock,
        array $data = []
    ) {
        $this->request = $request;
        parent::__construct(
            $url,
            $htmlPagerBlock,
            $data
        );
    }

    /**
     * Get item label wrapped in span with active/inactive class
     *
     * @return string
     */
    public function getLabel()
    {
        return '<span' . ($this->isActive() ? ' class="active"' : '') . '>' . $this->getData('label') . '</span>';
    }

    /**
     * Check whether the filter item is the selected one based on url params
     *
     * @return boolean
     */
    public function isActive()
    {
        $filters = $this->getCurrentFilters();
        if (empty($filters)) {
            return false;
        }
        if (!is_array($filters)) {
            $filters = [$filters];
        }
        return in_array($this->getValue(), $filters);
    }

    /**
     * Get filter item url
     *
     * @return string
     */
    public function getUrl()
    {
        $value = $this->getValue();

        $filters = $this->getCurrentFilters();
        if (!is_array($filters)) {
            $filters = [$filters];
        }
        if (!in_array($value, $filters)) {
            $filters[] = $value;
        } else {
            unset($filters[array_search($value, $filters)]);
        }

        $query = [
            $this->getFilter()->getRequestVar() => $filters,
            // exclude current page from urls
            $this->_htmlPagerBlock->getPageVarName() => null,
        ];
        return $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
    }

    /**
     * Get the current filter items from the url params
     *
     * @return array|string
     */
    protected function getCurrentFilters()
    {
        return $this->request->getParam($this->getFilter()->getRequestVar(), []);
    }
}
