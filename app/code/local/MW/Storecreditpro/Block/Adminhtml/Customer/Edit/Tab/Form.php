<?php
class MW_Storecreditpro_Block_Adminhtml_Customer_Edit_Tab_Form extends Mage_Adminhtml_Block_Template
{
	protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('mw_storecreditpro/customer/edit/form.phtml');
    }
    
	protected function _prepareLayout()
    {
        $mw_form = $this->getLayout()
            ->createBlock('storecreditpro/adminhtml_customer_edit_tab_storecredit_form');

        $this->setChild('mw_storecredit_form', $mw_form);

        return parent::_prepareLayout();
    }
}
	