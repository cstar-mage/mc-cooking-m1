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
class MD_Membership_Model_Plans extends Mage_Core_Model_Abstract
{
    const BILLING_PERIOD_DAILY = 'day';
    const BILLING_PERIOD_MONTHLY = 'month';
    const BILLING_PERIOD_WEEKLY = 'week';
    const BILLING_PERIOD_QUARTERLY = 'quarter';
    const BILLING_PERIOD_BIMONTHLY = 'bi_month';
    const BILLING_PERIOD_YEARLY = 'year';
    
    const DEFINED_BY_CUSTOMER = 'by_customer';
    const DEFINED_PURCHASE = 'day_purchase';
    const DEFINED_LAST_DAY_OF_MONTH = 'last_day_month';
    const DEFINED_DAY_OF_MONTH = 'day_month';
    
    const SUBSCRIPTION_LIFETIME = 0;
    const SUBSCRIPTION_LIMITED = 1;
    
    const SUBSCRIPTION_STATUS_ENABLED = 1;
    const SUBSCRIPTION_STATUS_DISABLED = 0;
    const SUBSCRIPTION_STATUS_ARCHIVED = 2;
    
    public function _construct() {
        parent::_construct();
        $this->_init('md_membership/plans');
    }
    
    protected function _beforeSave() {
        parent::_beforeSave();
        
        if(!$this->getId()){
            $this->setCreatedAt(Mage::getSingleton('core/date')->gmtDate());
        }
        $this->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
        $urlKey = $this->getUrlKey();
        
        if(!$urlKey){
            $urlKey = preg_replace('#[^0-9a-z]+#i', '-', Mage::helper('catalog/product_url')->format($this->getTitle()));
            $urlKey = strtolower($urlKey);
            $urlKey = trim($urlKey, '-');
        }
        $this->setUrlKey($urlKey);
        if(!$this->getIsLimited()){
            $this->setTotalOccurences(null);
        }
        $storeIds = $this->getStoreIds();
        if($this->getStartDateDefined() != self::DEFINED_DAY_OF_MONTH){
            $this->setDayOfMonth(null);
        }
        if($this->getTrialPeriodCount() <= 0){
            $this->setTrialAmount(0);
        }
        if(is_array($storeIds) && count($storeIds) > 0){
            $this->setStoreIds(implode(",",$storeIds));
        }else{
            $this->setStoreIds(null);
        }
    }
    
    public function getMembershipStorePlans()
    {
        $collection = $this->getCollection()
                            ->addFieldToFilter('status',array('eq'=>self::SUBSCRIPTION_STATUS_ENABLED))
                            ->addFieldToFilter('store_ids',array(array('finset'=>0),array('finset'=>Mage::app()->getStore()->getId())));
        
        //return ($collection->count() > 0) ? $collection: null;
        return $collection;
    }
    
    public function getPlanUrl()
    {
        $membershipUrlKey = trim(Mage::getStoreConfig("md_membership/membership_list/url_key"),'/');
        $suffix = trim(Mage::getStoreConfig("md_membership/membership_list/url_suffix"),'/');
        $urlKey = $membershipUrlKey.'/'.$this->getUrlKey();
        
        $urlKey .= (strlen($suffix) > 0 || $suffix != '') ? '.'.str_replace('.','',$suffix): '/';
        return Mage::getUrl().$urlKey;
    }
    
    public function getImageUrl($width=null,$height=null)
    {
        $image = $this->getImage();
        //if($image){
           $path = Mage::helper('md_membership')->getResizedImage($image,$width,$height);
           //return $path;
        //}
        return $path;
    }
    
    public function getPlanByUrlKey($urlKey = null){
        if($urlKey){
            $collection = $this->getCollection()
                            ->addFieldToFilter('url_key',array('eq'=>$urlKey));

            return ($collection->count() > 0) ? $collection->getFirstItem(): null;
        }
        return null;
    }
    
    public function getProfileStartDate()
    {
        $type = $this->getStartDateDefined();
        $dateString = array();
        //$date = Mage::getSingleton('core/date')->date('Y-m-d',Mage::app()->getLocale()->date(Mage::getSingleton('core/date')->gmtTimestamp(), null, null));
        $day = date("d",Mage::getSingleton('core/date')->gmtTimestamp());
        $month = date("m",Mage::getSingleton('core/date')->gmtTimestamp());
        $year = date("Y",Mage::getSingleton('core/date')->gmtTimestamp());
        $lastDay = date("t",Mage::getSingleton('core/date')->gmtTimestamp());
        $planDayOfMonth = (!is_null($this->getDayOfMonth())) ? $this->getDayOfMonth(): $day;
        
        switch($type){
            case self::DEFINED_DAY_OF_MONTH:
                                    if($planDayOfMonth >= $day){
                                        $dateString[0] = $year;
                                        $dateString[1] = $month;
                                        $dateString[2] = ($planDayOfMonth == 31 && $lastDay == 30) ? $lastDay: $planDayOfMonth;
                                    }else{
                                        $dateString[0] = $year;
                                        $dateString[1] = ($month == 12) ? 1: $month + 1;
                                        $dateString[2] = $planDayOfMonth;
                                    }
                                    break;
            case self::DEFINED_LAST_DAY_OF_MONTH:
                                    $dateString[0] = $year;
                                    $dateString[1] = $month;
                                    $dateString[2] = $lastDay;
                                    break;
            case self::DEFINED_PURCHASE:
                                    $dateString[0] = $year;
                                    $dateString[1] = $month;
                                    $dateString[2] = $day;
                                    break;
            default:
                    $dateString = array();
                    break;
        }
        
        return (count($dateString) > 0) ? implode("-",$dateString): null;
    }
    
    public function getSubscribersCollection($ids = null)
    {
        if(!is_array($ids) && !is_null($ids)){
            $ids = array($ids);
        }
        $collection = Mage::getModel('md_membership/subscribers')->getCollection()
                                    ->addFieldToFilter('plan_id',array('eq'=>$this->getId()));
        
        if(is_array($ids) && count($ids)){
            $collection->addFieldToFilter('subscriber_id',array('in'=>$ids));
        }
        
        if($collection->count() > 0){
            return $collection;
        }else{
            return null;
        }
    }
    
    public function getBillingPeriodLabel()
    {
        $billingPeriod = $this->getBillingPeriod();
        $label = Mage::helper('md_membership')->getSubscriptionPlanLabel($billingPeriod);
        return $label;
    }
    
    public function hasActiveSubscribers()
    {
        $flag = false;
        $collection = Mage::getModel('md_membership/subscribers')->getCollection()
                            ->addFieldToFilter('plan_id',array('eq'=>$this->getId()))
                            ->addFieldToFilter('status',array('in'=>array(MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_ACTIVE,MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_SUSPENDED)));
        
        if($collection->count() > 0){
            $flag = true;
        }
        return $flag;
    }
}

