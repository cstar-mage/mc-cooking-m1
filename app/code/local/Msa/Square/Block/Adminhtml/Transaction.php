<?php
class Msa_Square_Block_Adminhtml_Transaction extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_headerText = Mage::helper('msa_square')->__('Square Transactions');
    
    $this->_blockGroup = 'msa_square';
    $this->_controller = 'adminhtml_transaction';
    
    parent::__construct();
  }
  
  protected function _prepareLayout()
  {
    $this->_removeButton('add');

    $this->_addButton('run_now', array(
        'label'     => Mage::helper('msa_square')->__('Run Now'),
        'onclick'   => 'setLocation(\'' . $this->getUrl('adminhtml/transaction/run') . '\')',
    ));
    
    return parent::_prepareLayout();
  }
}