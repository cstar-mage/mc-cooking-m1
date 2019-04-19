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
class MD_Membership_Model_Subscribers extends Mage_Core_Model_Abstract
{
    const SUBSCRIPTION_STATUS_ACTIVE = 1;
    const SUBSCRIPTION_STATUS_EXPIRED = 2;
    const SUBSCRIPTION_STATUS_SUSPENDED = 3;
    const SUBSCRIPTION_STATUS_CANCELLED = 4;
    const SUBSCRIPTION_STATUS_TERMINATED = 5;
    
    public function _construct() {
        parent::_construct();
        $this->_init('md_membership/subscribers');
    }
    
    public function getPlan()
    {
        $planId = $this->getPlanId();
        $planData = $this->getPlanData();
        if(!is_null($planData) && strlen($planData) > 0){
            $planObject = new Varien_Object(unserialize($planData));
        }else{
            $planObject = Mage::getModel('md_membership/plans')->load($planId);
        }
        return $planObject;
    }
    
    public function getCustomer()
    {
        $customerId = $this->getCustomerId();
        $customerObject = Mage::getModel('customer/customer')->load($customerId);
        return $customerObject;
    }
    
    protected function _beforeSave() {
        parent::_beforeSave();
        
        if(!$this->getId()){
            $this->setCreatedAt(Mage::getSingleton('core/date')->gmtDate());
            $this->setStoreId(Mage::app()->getStore()->getId());
            
            $incrCollection = Mage::getModel('md_membership/increments')->getCollection()
                                        ->addFieldToFilter('store_id',array('eq'=>Mage::app()->getStore()->getId()));
            
            $incrementItem = $incrCollection->getFirstItem();
            $customerId = $this->getCustomerId();
            $groupId = $this->getPlan()->getAssignedGroupId();
            if($groupId > 0){
                Mage::getModel('customer/customer')->load($customerId)->setGroupId($groupId)->save();
            }
            $incrementItem->setIncrementLastId($this->getReservedIncrementId())->setId($incrementItem->getId())->save();
            
        }
        $this->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
    }
    
    public function getMembershipByCustomer(Mage_Customer_Model_Customer $customer)
    {
        if($customer instanceof Mage_Customer_Model_Customer){
            $collection = $this->getCollection()
                            ->addFieldToFilter('customer_id',array('eq'=>$customer->getId()))
                            ->addFieldToFilter('store_id',array('eq'=>Mage::app()->getStore()->getId()))
                            ->setOrder('subscriber_id','DESC');
            return $collection;                
                          
        }
        return null;
    }
    
    public function canActivateProfile()
    {
        return in_array($this->getStatus(),array(self::SUBSCRIPTION_STATUS_SUSPENDED));
    }
    
    public function canCancelProfile()
    {
        return in_array($this->getStatus(),array(self::SUBSCRIPTION_STATUS_ACTIVE,self::SUBSCRIPTION_STATUS_SUSPENDED));
    }
    
    public function canSuspendProfile()
    {
        return in_array($this->getStatus(),array(self::SUBSCRIPTION_STATUS_ACTIVE));
    }
    
    public function canRenewProfile()
    {
        return in_array($this->getStatus(),array(self::SUBSCRIPTION_STATUS_EXPIRED));
    }
    
    public function getStatusLabel()
    {
        $status = $this->getStatus();
        $label = null;
        $labelsArray = Mage::helper('md_membership')->getStatusLabels();
        if(array_key_exists($status,$labelsArray)){
            $label = $labelsArray[$status];
        }
        return $label;
    }
    
    public function getBillingAddressFormated()
    {
        $html = null;
        if($this->getCustomerAddressId() > 0){
            $billingAddress = $this->getCustomer()->getAddressById($this->getCustomerAddressId());
            $html = $billingAddress->format('html');
        }
        return $html;
    }
    
    public function getPaymentMethodLabel()
    {
        $paymentMethod = $this->getPaymentMethod();
        $paymentLabel = null;
        $labels = Mage::helper('md_membership')->getPaymentArray();
        if(array_key_exists($paymentMethod,$labels)){
            $paymentLabel = $labels[$paymentMethod];
        }
        return $paymentLabel;
    }
    
    public function getReservedIncrementId()
    {
        $storeId = Mage::app()->getStore()->getId();
        $collection = Mage::getModel('md_membership/increments')->getCollection()
                        ->addFieldToFilter('store_id',array('eq'=>$storeId));
        $incrementItem = $collection->getFirstItem();
        $lastUsedIncrementId = $incrementItem->getIncrementLastId();
        $newIncrementId = $lastUsedIncrementId + 1;
        return $newIncrementId;
    }
    
    public function getPaymentSummary()
    {
        $collection = Mage::getModel('md_membership/payments')->getCollection()
                        ->addFieldToFilter('profile_id',array('eq'=>$this->getProfileId()));
                        
        if(!is_null($this->getReferenceId())){
            $collection->addFieldToFilter('reference_id',array('eq'=>$this->getReferenceId()));
        }
        if($collection->count() > 0){
            return $collection;
        }else{
            return null;
        }
    }
}

