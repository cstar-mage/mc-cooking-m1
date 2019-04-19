<?php

class BroSolutions_SmartSlides_Block_Adminhtml_Smartslides_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'entity_id';
        $this->_controller = 'adminhtml_smartslides';
        $this->_blockGroup = 'smartslides';
        $this->_mode = 'edit';
        parent::__construct();
        $this->setId('smartslides_edit');

    }
    public function getBackUrl()
    {
        parent::getBackUrl();
        return $this->getUrl('*/*/slides');
    }
}