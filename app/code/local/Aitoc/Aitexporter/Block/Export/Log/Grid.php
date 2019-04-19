<?php
/**
 * Orders Export and Import
 *
 * @category:    Aitoc
 * @package:     Aitoc_Aitexporter
 * @version      1.2.11
 * @license:     xwB5BO6b8ADSZdWMZBvj82HAnNdHAZvfJuyG2JiSFP
 * @copyright:   Copyright (c) 2015 AITOC, Inc. (http://www.aitoc.com)
 */
class Aitoc_Aitexporter_Block_Export_Log_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->_exportTypes = array();
        $this->_rssLists    = array();

        if(version_compare(Mage::getVersion(),'1.6.0.0','ge')) 
        {
        	$this->removeColumn('action');
        }
        
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) 
        {
            $this->addColumn('action', array(
                'header'    => Mage::helper('sales')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                    'caption' => Mage::helper('sales')->__('View'),
                    'url'     => array('base' => 'adminhtml/sales_order/view'), 
                    'field'   => 'order_id'
                    )),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
                ));
        }

        return $this;
    }

    protected function _prepareMassaction()
    {
        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/viewLogGrid', array('_current' => true));
    }

    protected function _prepareCollection()
    {
        $currentExport = Mage::registry('current_export');
        /* @var $currentExport Aitoc_Aitexporter_Model_Export */
        
		if(version_compare(Mage::getVersion(),'1.4.1','ge')) 
        {
	        $collection = Mage::getResourceModel($this->_getCollectionClass());
	        /* @var $collection Mage_Sales_Model_Mysql4_Order_Grid_Collection */
        }
        else 
        {
                $exportCollection = Mage::getResourceModel('aitexporter/export_order_collection');
                $exportCollection->getSelect()->where('export_id=?',(int)($currentExport->getId()));
                $order_ids = array();
                foreach($exportCollection as $item)
                {
                   $order_ids[] = $item->getOrderId(); 
                }
			$collection = Mage::getResourceModel('sales/order_collection')
	            ->addAttributeToSelect('*')
	            ->joinAttribute('billing_firstname', 'order_address/firstname', 'billing_address_id', null, 'left')
	            ->joinAttribute('billing_lastname', 'order_address/lastname', 'billing_address_id', null, 'left')
	            ->joinAttribute('shipping_firstname', 'order_address/firstname', 'shipping_address_id', null, 'left')
	            ->joinAttribute('shipping_lastname', 'order_address/lastname', 'shipping_address_id', null, 'left')
	            ->addExpressionAttributeToSelect('billing_name',
	                'CONCAT({{billing_firstname}}, " ", {{billing_lastname}})',
	                array('billing_firstname', 'billing_lastname'))
	            ->addExpressionAttributeToSelect('shipping_name',
	                'CONCAT({{shipping_firstname}}, " ", {{shipping_lastname}})',
	                array('shipping_firstname', 'shipping_lastname'));
                    $collection->getSelect()->where('e.entity_id IN (?)',(array)$order_ids);
        }
        
        $currentExport->prepareOrderCollection($collection);

        $this->setCollection($collection);

        if ($this->getCollection()) 
        {
            $this->_preparePage();

            $columnId = $this->getParam($this->getVarNameSort(), $this->_defaultSort);
            $dir      = $this->getParam($this->getVarNameDir(), $this->_defaultDir);
            $filter   = $this->getParam($this->getVarNameFilter(), null);

            if (is_null($filter)) 
            {
                $filter = $this->_defaultFilter;
            }

            if (is_string($filter)) 
            {
                $data = $this->helper('adminhtml')->prepareFilterString($filter);
                $this->_setFilterValues($data);
            }
            elseif ($filter && is_array($filter)) 
            {
                $this->_setFilterValues($filter);
            }
            elseif (0 !== sizeof($this->_defaultFilter)) 
            {
                $this->_setFilterValues($this->_defaultFilter);
            }

            if (isset($this->_columns[$columnId]) && $this->_columns[$columnId]->getIndex()) 
            {
                $dir = (strtolower($dir)=='desc') ? 'desc' : 'asc';
                $this->_columns[$columnId]->setDir($dir);
                if(version_compare(Mage::getVersion(),'1.5.0.0','ge')) 
        		{
                	$this->_setCollectionOrder($this->_columns[$columnId]);
        		}
            }

            if (!$this->_isExport) 
            {
                $this->getCollection()->load();
                $this->_afterLoadCollection();
            }
        }

        return $this;
    }

    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) 
        {
            return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getId()));
        }

        return false;
    }
}