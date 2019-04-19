<?php

class BroSolutions_SmartSlides_Block_Adminhtml_Smartslides_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
        $this->_smartslidesModel = Mage::getModel('smartslides/smartslides');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('smartslides/smartslides')->getCollection();
        $collection->getSelect()
            ->order('entity_id', 'DESC');

        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('core');

        $this->addColumn('entity_id', array(
            'header' => $helper->__('Slide #'),
            'index'  => 'entity_id',
            'width' => '10px',

        ));

        $this->addColumn('title', array(
            'header' => $helper->__('Title'),
            'index'  => 'title',
            'width' => '100px',
        ));


        $this->addColumn('subtitle', array(
            'header'       => $helper->__('Subtitle'),
            'index'        => 'subtitle',
            'width' => '100px',
        ));

        $this->addColumn('status', array(
            'header'       => $helper->__('Status'),
            'index'        => 'status',
            'width' => '10px',
            'type'  => 'options',
            'options' => Mage::getSingleton('smartslides/smartslides')->getOptionArray(),
        ));

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/editslide', array(
                'store'=>$this->getRequest()->getParam('store'),
                'slide_id'=>$row->getId())
        );
    }
}