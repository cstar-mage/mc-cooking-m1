<?php

class MW_Storecreditpro_Model_Action extends Varien_Object
{
    const ADDITION				= 1;
    const SUBTRACTION			= -1;
	

    static public function getOptionArray()
    {
        return array(
        	self::SUBTRACTION  			 	=> Mage::helper('storecreditpro')->__('Subtraction'),
            self::ADDITION    				=> Mage::helper('storecreditpro')->__('Addition'),
        );
    }
}