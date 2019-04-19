<?php

class MW_Storecreditpro_Block_Adminhtml_Member_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {   
      $form_member_detail = new Varien_Data_Form();
      $this->setForm($form_member_detail);
      
      $fieldset1 = $form_member_detail->addFieldset('base_fieldset', array('legend'=>Mage::helper('storecreditpro')->__('Credit Information')));
      
      $fieldset = $form_member_detail->addFieldset('credit_form', array('legend'=>Mage::helper('storecreditpro')->__('Manually Adjust Credit Balance')));
      
      $customer_id = $this->getRequest()->getParam('id');      
      $customer = Mage::getModel('customer/customer')->load($customer_id);
	  $customer_email = $customer->getEmail();
	  $credit = Mage::getModel('storecreditpro/customer')->load($customer_id)->getData('credit_balance');
	  
      $fieldset1->addField('storecredit', 'note', array(
          'label'     => Mage::helper('storecreditpro')->__('Customer Balance'),
      	  'name'  	=> 'mw_storecredit',
          'text'     => Mage::helper('core')->currency($credit),
      ));

      $fieldset1->addField('customer_email', 'note', array(
          'label'     => Mage::helper('storecreditpro')->__('Customer Email'),
          'text'      => Mage::helper('storecreditpro')->getLinkCustomer($customer_id,$customer_email),
      ));
      $fieldset->addField('amount', 'text',
             array(
                    'label' 	=> Mage::helper('storecreditpro')->__('Amount'),
                    'name'  	=> 'mw_storecredit_amount',
             		'class'		=> 'validate-digits'
             )
        );
        
        $fieldset->addField('action', 'select',
             array(
                    'label' 	=> Mage::helper('storecreditpro')->__('Action'),
                    'name'  	=> 'mw_storecredit_action',
             		'options'	=> Mage::getModel('storecreditpro/action')->getOptionArray()
             )
        );
        
        $fieldset->addField('comment', 'textarea',
             array(
                    'label' 	=> Mage::helper('storecreditpro')->__('Comment'),
                    'name'  	=> 'mw_storecredit_comment',
             		'style'		=>	'height:100px'
             )
        );
      $form_member_detail->getElement('action')->setValue(1);
      
      return parent::_prepareForm();
  }

}