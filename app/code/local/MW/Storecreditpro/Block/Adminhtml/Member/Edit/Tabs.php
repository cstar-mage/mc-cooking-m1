<?php

class MW_Storecreditpro_Block_Adminhtml_Member_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('member_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('storecreditpro')->__('Credit Member Information'));
  }

  protected function _beforeToHtml()
  {   
  	  
      $this->addTab('form_member_detail', array(
          'label'     => Mage::helper('storecreditpro')->__('General information'),
          'title'     => Mage::helper('storecreditpro')->__('General information'),
          'content'   => $this->getLayout()->createBlock('storecreditpro/adminhtml_member_edit_tab_form')->toHtml(),
      ));

      $this->addTab('form_member_transaction', array(
          'label'     => Mage::helper('storecreditpro')->__('Transaction History'),
          'title'     => Mage::helper('storecreditpro')->__('Transaction History'),
          'content'   => $this->getLayout()->createBlock('storecreditpro/adminhtml_member_edit_tab_transaction')->toHtml(),
          ));
     
      return parent::_beforeToHtml();
  }
}