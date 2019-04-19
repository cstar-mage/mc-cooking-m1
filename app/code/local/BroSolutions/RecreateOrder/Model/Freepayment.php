<?php
class BroSolutions_RecreateOrder_Model_Freepayment extends Mage_Payment_Model_Method_Abstract
{

    protected $_code = 'freepayment';

    protected $_isInitializeNeeded      = true;
    protected $_canUseInternal          = true;
    protected $_canUseForMultishipping  = false;


    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('customcard/standard/redirect', array('_secure' => true));
    }

    public function isAvailable($quote = NULL)
    {
        if(Mage::app()->getStore()->isAdmin()){
            return true;
        }
        return false;
    }

}