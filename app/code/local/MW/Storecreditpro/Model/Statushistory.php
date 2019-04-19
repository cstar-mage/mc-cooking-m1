<?php

class MW_Storecreditpro_Model_Statushistory extends Varien_Object
{
    const PENDING				= 1;		
    const COMPLETE				= 2;
    const CANCELLED				= 3;
    const CLOSED				= 4;
	

    static public function getOptionArray()
    {
        return array(
            self::PENDING    				=> Mage::helper('storecreditpro')->__('Pending'),
            self::COMPLETE  			 	=> Mage::helper('storecreditpro')->__('Complete'),
            self::CANCELLED  			 	=> Mage::helper('storecreditpro')->__('Cancelled'),
            self::CLOSED  			 	    => Mage::helper('storecreditpro')->__('Closed'),

        );
    }
    
    static public function getLabel($type)
    {
    	$options = self::getOptionArray();
    	return $options[$type];
    }
}