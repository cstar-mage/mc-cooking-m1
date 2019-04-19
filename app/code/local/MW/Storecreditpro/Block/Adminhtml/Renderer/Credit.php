<?php

class MW_Storecreditpro_Block_Adminhtml_Renderer_Credit extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
    	if (empty($row['entity_id'])) return '0';
    	$credit = 0;
    	$credit = Mage::getModel('storecreditpro/customer')->load($row['entity_id'])->getCreditBalance();
    	
    	if(!isset($credit) || $credit == 0){
    		$credit = '0';
    		return $credit;
    	} 
    	return Mage::helper('core')->currency($credit,false,false);
    }

}