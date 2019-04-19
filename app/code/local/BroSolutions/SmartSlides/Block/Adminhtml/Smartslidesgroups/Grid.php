<?php

class BroSolutions_SmartSlides_Block_Adminhtml_Smartslidesgroups_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected $_smartslidesModel = NULL;
    public function __construct()
    {
        parent::__construct();
        $this->setId('smartslides_grid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->_smartslidesModel = Mage::getModel('smartslides/smartslidesgroups');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('smartslides/smartslidesgroups')->getCollection();
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('core');

        $this->addColumn('entity_id', array(
            'header' => $helper->__('Slide group #'),
            'index'  => 'entity_id',
            'width' => '20px',

        ));

        $this->addColumn('name', array(
            'header' => $helper->__('Name'),
            'index'  => 'name',
            'width' => '200px',

        ));

        $this->addColumn('status', array(
            'header'       => $helper->__('Status'),
            'index'        => 'status',
            'width' => '100px',
        ));

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/editslidersgroup', array(
                'store'=>$this->getRequest()->getParam('store'),
                'slidergroup_id'=>$row->getId())
        );
    }
}