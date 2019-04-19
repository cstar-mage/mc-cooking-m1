<?php
class MW_Storecreditpro_Block_Adminhtml_Member extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_member';
    $this->_blockGroup = 'storecreditpro';
    $this->_headerText = Mage::helper('storecreditpro')->__('Customer Credit');
    parent::__construct();
    $this->_removeButton('add');
        
  }
}