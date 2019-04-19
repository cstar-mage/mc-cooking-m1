<?php

class BroSolutions_SmartSlides_Block_Adminhtml_Smartslidesgroups_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_objectId = 'entity_id';
        $this->_controller = 'adminhtml_smartslidesgroups';
        $this->_blockGroup = 'smartslides';
        //$this->_mode = 'edit_tab';
        //$this->setId('smartslidesgroups_edit');
    }

    public function getBackUrl()
    {
        parent::getBackUrl();
        return $this->getUrl('*/*/slidesgroups');
    }
}