<?php

class MW_Storecreditpro_Block_Adminhtml_Renderer_Amount extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
    	if (empty($row['history_id'])) return '';
    	$history = Mage::getModel('storecreditpro/history')->load($row['history_id']);
		$result = '';
		$amount = Mage::helper('core')->currency($history->getAmount(),false,false);
    	$result = Mage::getModel('storecreditpro/type')->getAmountWithSign($amount,$history->getTransactionType()); 
    	return $result;
    }

}