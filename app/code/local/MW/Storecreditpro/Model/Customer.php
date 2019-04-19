<?php

class MW_Storecreditpro_Model_Customer extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('storecreditpro/customer');
    }
    
	public function addCredit($credit)
	{
		$this->setCreditBalance($this->getCreditBalance()+ $credit);
		$this->save();
	}
}