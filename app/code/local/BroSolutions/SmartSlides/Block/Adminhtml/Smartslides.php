<?php

class BroSolutions_SmartSlides_Block_Adminhtml_Smartslides extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'smartslides';
        $this->_controller = 'adminhtml_smartslides';
        $this->_headerText = Mage::helper('core')->__('Slides');
        parent::__construct();
        $this->_updateButton('add', 'label', Mage::helper('catalog')->__('Add Slide'));
        $this->_updateButton('add', 'onclick', "setLocation('{$this->getUrl('*/*/editslide')}')");
    }
    protected function _prepareLayout()
    {
        $this->setChild('grid', $this->getLayout()->createBlock('smartslides/adminhtml_smartslides_grid', 'slides.grid'));
        return parent::_prepareLayout();
    }
}