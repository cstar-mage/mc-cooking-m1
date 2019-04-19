<?php

class BroSolutions_SmartSlides_Block_Adminhtml_Smartslidesgroups_Edit_Tabs extends  Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('slides_group_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('core')->__('Manage Slides Groups'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label' => Mage::helper('core')->__('General Information'),
            'title' => Mage::helper('core')->__('General Information'),
            'content' => $this->getLayout()->createBlock('smartslides/adminhtml_smartslidesgroups_edit_tab_form')->toHtml(),
        ));
        if($this->getRequest()->getParam('active_tab') == 'form_slides'){
            $this->addTab('form_slides', array(
                'label' => Mage::helper('core')->__('Slides'),
                'title' => Mage::helper('core')->__('Slides'),
                'url'   => $this->getUrl('*/*/slidesgrid', array('_current' => true)),
                'class' => 'ajax',
                'active' => true,
            ));
        } else {
            $this->addTab('form_slides', array(
                'label' => Mage::helper('core')->__('Slides'),
                'title' => Mage::helper('core')->__('Slides'),
                'url'   => $this->getUrl('*/*/slidesgrid', array('_current' => true)),
                'class' => 'ajax',
            ));
        }

        return parent::_beforeToHtml();
    }

}