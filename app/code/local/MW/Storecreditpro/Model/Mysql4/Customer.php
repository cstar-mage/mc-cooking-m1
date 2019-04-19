<?php

class MW_Storecreditpro_Model_Mysql4_Customer extends Mage_Core_Model_Mysql4_Abstract
{
	protected $_isPkAutoIncrement    = false;
    public function _construct()
    {    
        $this->_init('storecreditpro/customer', 'customer_id');
    }
}