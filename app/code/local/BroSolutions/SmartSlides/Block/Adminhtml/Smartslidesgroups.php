<?php

class BroSolutions_SmartSlides_Block_Adminhtml_Smartslidesgroups extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'smartslides';
        $this->_controller = 'adminhtml_smartslidesgroups';
        $this->_headerText = Mage::helper('core')->__('Slides Groups');
        parent::__construct();
        $this->_updateButton('add', 'label', Mage::helper('catalog')->__('Add Slides Group'));
        $this->_updateButton('add', 'onclick', "setLocation('{$this->getUrl('*/*/editslidersgroup')}')");

    }

    protected function _prepareLayout()
    {
        $this->setChild('grid', $this->getLayout()->createBlock('smartslides/adminhtml_smartslidesgroups_grid', 'product.grid'));
        return parent::_prepareLayout();
    }
}