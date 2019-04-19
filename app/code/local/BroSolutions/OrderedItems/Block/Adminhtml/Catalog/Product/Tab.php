<?php

class BroSolutions_OrderedItems_Block_Adminhtml_Catalog_Product_Tab
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface {

    /**
     * Set the template for the block
     *
     */
    public function _construct()
    {
        //parent::__construct();
        $this->setId('oitemsGrid');
        $this->setDefaultSort('entity_id');
    }

    /**
     * Retrieve the label used for the tab relating to this block
     *
     * @return string
     */
    public function getTabLabel()
    {
        return $this->__('Ordered items');
    }

    /**
     * Retrieve the title used by this tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->__('Ordered items');
    }

    /**
     * Determines whether to display the tab
     * Add logic here to decide whether you want the tab to display
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Stops the tab being hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * AJAX TAB's
     * If you want to use an AJAX tab, uncomment the following functions
     * Please note that you will need to setup a controller to recieve
     * the tab content request
     *
     */
    /**
     * Retrieve the class name of the tab
     * Return 'ajax' here if you want the tab to be loaded via Ajax
     *
     * return string
     */


    protected function _prepareCollection()
    {
        $collection = Mage::helper('oitems')->getOrderedProductsCollection($this->_getProduct()->getId(), false);
        /* @var $collection Mage_Sitemap_Model_Mysql4_Sitemap_Collection */
        $crediMemoTableName = Mage::getSingleton('core/resource')->getTableName('sales_flat_creditmemo');
        $collection->getSelect()->joinLeft(
            array('cm' => $crediMemoTableName),
            '`main_table`.`order_id` = `cm`.`order_id`',
            array('order_exist' => 'cm.order_id')
        );
        $collection->getSelect()->where('cm.order_id IS NULL');
        $collection->addFieldToFilter('order_entity.status', array('neq' => Mage_Sales_Model_Order::STATE_CANCELED));
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _getProduct()
    {
        return Mage::registry('current_product');
    }


    protected function _prepareColumns()
    {

        $this->addColumn('billing_name', array(
            'header'    => Mage::helper('core')->__('Customer name'),
            'index'     => 'billing_name'
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('core')->__('Ordered at:'),
            'index'     => 'created_at'
        ));


        $this->addColumn('increment_id', array(
            'header'    => Mage::helper('core')->__('Order #:'),
            'index'     => 'increment_id'
        ));

        $this->addColumn('customer_email', array(
            'header'    => Mage::helper('core')->__('Customer Email:'),
            'index'     => 'customer_email'
        ));
        $this->addColumn('qty_ordered', array(
            'header'    => Mage::helper('core')->__('Qty Ordered:'),
            'index'     => 'qty_ordered'
        ));
        $this->addColumn('name', array(
            'header'    => Mage::helper('core')->__('Product Name:'),
            'index'     => 'name'
        ));
        $this->addColumn('change_class',
            array(
                'header'=> Mage::helper('catalog')->__('Change class'),
                'renderer'  => 'BroSolutions_RecreateOrder_Block_Adminhtml_Sales_Order_View_Tab_Renderer_Changeclasslink',
                'filter'    => false,
                'sortable'  => false,

            ));

        $this->addColumn('action',
            array(
                'header'    => Mage::helper('core')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getOrderId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('sales')->__('View'),
                        'url'     => array('base'=>'*/sales_order/view'),
                        'field'   => 'order_id',
                        'data-column' => 'action',
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
            ));

        return parent::_prepareColumns();
    }
}
