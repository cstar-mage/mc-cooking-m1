<?php
class BroSolutions_DateFilter_Block_Catalog_Layer_View extends Mage_Catalog_Block_Layer_View
{
    const SALE_FILTER_POSITION = 5;

    protected $_saleBlockName;

    protected function _initBlocks()
    {
        parent::_initBlocks();

        $this->_saleBlockName = 'datefilter/catalog_layer_filter_date';
    }

    protected function _prepareLayout()
    {
        $saleBlock = $this->getLayout()->createBlock($this->_saleBlockName)
            ->setLayer($this->getLayer())
            ->init();

        $this->setChild('date_filter', $saleBlock);

        return parent::_prepareLayout();
    }

    public function getFilters()
    {
        $filters = parent::getFilters();

        if (($saleFilter = $this->_getDateFilter())) {
            return array($saleFilter);
        }

        return $filters;
    }

    /**
     * Get sale filter block
     *
     * @return Mage_Catalog_Block_Layer_Filter_Sale
     */
    protected function _getDateFilter()
    {
        return $this->getChild('date_filter');
    }
    public function canShowOptions()
    {
        return true;
    }
}