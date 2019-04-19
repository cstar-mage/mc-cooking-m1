<?php
/**
* Magedelight
* Copyright (C) 2015 Magedelight <info@magedelight.com>
*
* NOTICE OF LICENSE
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
*
* @category MD
* @package MD_Membership
* @copyright Copyright (c) 2015 Mage Delight (http://www.magedelight.com/)
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
* @author Magedelight <info@magedelight.com>
*/
class MD_Membership_Model_Payment_Abstract
{
    protected $_billingAddressId = null;
    protected $_membershipPlanId = null;
    protected $_cardDetails = array();
    protected $_subscriptionDate = null;
    protected $_subscriber = null;
    protected $_plan = null;
    
    public function setBillingAddressId($id){
        $this->_billingAddressId = $id;
        return $this;
    }
    
    public function setSubscriber($subscriber){
        $this->_subscriber = $subscriber;
        return $this;
    }
    
    public function getSubscriber()
    {
        return $this->_subscriber;
    }
    
    public function setSubscriptionStartDate($date)
    {
        if(strlen($date) > 0 && !is_null($date)){
        $this->_subscriptionDate = $date;
        }
        return $this;
    }
    
    public function getSubscriptionStartDate(){
        return $this->_subscriptionDate;
    }
    
    public function setMembershipPlanId($id){
        $this->_membershipPlanId = $id;
        return $this;
    }
    
    public function getBillingAddressId()
    {
        return $this->_billingAddressId;
    }
    
    public function getMembershipPlanId()
    {
        return $this->_membershipPlanId;
    }
    
    public function setCardDetails($array){
        $this->_cardDetails = $array;
        return $this;
    }
    
    public function getCardDetails(){
        return $this->_cardDetails;
    }
    
    public function pay()
    {
        return $this;
    }
    
    public function getStoreId()
    {
        if(!is_null($this->_subscriber) && is_object($this->_subscriber)){
            return $this->_subscriber->getStoreId();
        }
        return Mage::app()->getStore()->getId();
    }
    
}

