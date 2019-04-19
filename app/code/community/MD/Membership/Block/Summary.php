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
class MD_Membership_Block_Summary extends Mage_Core_Block_Template
{
    protected $_helper = null;
    protected $_customer = null;
    protected $_plan = null;
    public function __construct(){
        parent::__construct();
        $this->_helper = Mage::getSingleton('md_membership');
        $this->setTemplate('md/membership/summary.phtml');
    }
    
    public function getCustomer()
    {
        if(!$this->_customer){
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        return $this->_customer;
    }
    
    public function getCurrentPlan()
    {
        if(!$this->_plan){
            $this->_plan = Mage::registry('current_plan');
        }
        return $this->_plan;
    }
    
    public function getAddressesHtmlSelect($type)
    {
        $addresses = $this->getCustomer()->getAddresses();
        $options = array();
        foreach($addresses as $address){
            $options[] = array(
                    'value' => $address->getId(),
                    'label' => $address->format('oneline')
                );
        }
        if ($type=='billing') {
                    $address = $this->getCustomer()->getPrimaryBillingAddress();
                } else {
                    $address = $this->getCustomer()->getPrimaryShippingAddress();
                }
                if ($address) {
                    $addressId = $address->getId();
                }
        if(count($options) > 0){
                $select = $this->getLayout()->createBlock('core/html_select')
                ->setName($type.'_address_id')
                ->setId($type.'-address-select')
                ->setClass('address-select')
                //->setExtraParams('onchange="'.$type.'.newAddress(!this.value)"')
                ->setValue($addressId)
                ->setOptions($options);

            //$select->addOption('', Mage::helper('checkout')->__('New Address'));

            return $select->getHtml();
        }else{
            return '';
        }
    }
    
    public function getActiveMethods()
    {
        $methods = Mage::getSingleton('payment/config')->getActiveMethods();
        $membershipMethods = array();
        foreach($methods as $code=>$method){
            
            if(Mage::helper('md_membership')->isAllowedMethod($code)){
                if($this->getCurrentPlan()->getBillingPeriod() == MD_Membership_Model_Plans::BILLING_PERIOD_DAILY && $code == Mage_Paygate_Model_Authorizenet::METHOD_CODE)
                {
                    continue;
                }
                $membershipMethods[$code] = $method;
            }
        }
        return $membershipMethods;
    }
    
    public function getPaymentPayUrl($plan)
    {
        return $this->getUrl('md_membership/index/pay',array('plan_id'=>$plan->getId(),'_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()));
    }
}

