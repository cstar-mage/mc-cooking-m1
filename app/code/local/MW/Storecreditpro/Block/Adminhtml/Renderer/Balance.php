<?php

class MW_Storecreditpro_Block_Adminhtml_Renderer_Balance extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
    	if (empty($row['history_id'])) return '';
    	$history = Mage::getModel('storecreditpro/history')->load($row['history_id']);
	
    	return Mage::helper('core')->currency($history->getBalance());
    }

}