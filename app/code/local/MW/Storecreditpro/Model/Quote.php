<?php

class MW_Storecreditpro_Model_Quote extends Mage_Core_Model_Abstract
{
    protected function _getSession()
    {
    	return Mage::getSingleton('checkout/session');
    }
    
	public function collectTotalBefore($argv)
    {
    	if(!Mage::helper('storecreditpro')->moduleEnabled())
		{
			$quote = $argv->getQuote();
			$quote->setMwStorecredit(0)->save();
		}
    }
}