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
class MD_Membership_Block_Customer_Subscriptions_View extends Mage_Core_Block_Template
{
    protected $_subscriber = null;
    protected $_customer = null;
    protected $_plan = null;
    public function __construct() {
        parent::__construct();
        $subscriptionId = $this->getRequest()->getParam('subscription_id',null);
        if(!is_null($subscriptionId)){
            $this->_subscriber = Mage::getModel('md_membership/subscribers')->load($subscriptionId);
        }
        $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        $this->setTemplate('md/membership/customer/subscriptions/view.phtml');
    }
    
    protected function _prepareLayout() {
        parent::_prepareLayout();
        if($this->getSubscription()->canActivateProfile() && $this->getSubscription()->getPaymentMethod() != Mage_Paygate_Model_Authorizenet::METHOD_CODE){
            $this->setChild('subscription_activate_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('md_membership')->__('Activate Profile'),
                    'class' => 'button',
                    'style' => 'background:#3CB861;',
                    'on_click' => 'confirmSetLocation(\'Are you sure want to activate this profile?\',\''.$this->getUrl('*/*/activeProfile',array('id'=>$this->getRequest()->getParam('subscription_id',null))).'\')'
                ))
        );
        }
        
        if($this->getSubscription()->canCancelProfile()){
            $this->setChild('subscription_cancel_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('md_membership')->__('Cancel Profile'),
                    'class' => 'button',
                    'style' => 'background:#F55600;',
                    'on_click' => 'confirmSetLocation(\'Are you sure want to cancel this profile?\',\''.$this->getUrl('*/*/cancelProfile',array('id'=>$this->getRequest()->getParam('subscription_id',null))).'\')'
                ))
        );
        }
        
        if($this->getSubscription()->canSuspendProfile() && $this->getSubscription()->getPaymentMethod() != Mage_Paygate_Model_Authorizenet::METHOD_CODE){
            $this->setChild('subscription_suspend_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('md_membership')->__('Suspend Profile'),
                    'class' => 'button',
                    'style' => 'background:#FF9C00;',
                    'on_click' => 'confirmSetLocation(\'Are you sure want to suspend this profile?\',\''.$this->getUrl('*/*/suspendProfile',array('id'=>$this->getRequest()->getParam('subscription_id',null))).'\')'
                ))
        );
        }    
    }
    public function getSubscription()
    {
        return $this->_subscriber;
    }
    
    public function getBillingAddressFormated()
    {
        $html = null;
        if(!is_null($this->getSubscription()) && $this->getSubscription()->getCustomerAddressId() > 0){
            $billingAddress = $this->getCustomer()->getAddressById($this->getSubscription()->getCustomerAddressId());
            $html = $billingAddress->format('html');
        }
        return $html;
    }
    
    public function getCustomer()
    {
        return $this->_customer;
    }
    
    public function getPlan()
    {
        if(is_null($this->_plan) && $this->getSubscription()->getId()){
            $this->_plan = $this->getSubscription()->getPlan();
        }
        return $this->_plan;
    }
    
    public function getBackUrl()
    {
        return $this->getUrl('md_membership/customer_subscriptions/list');
    }
    
    public function getTitle()
    {
        return Mage::helper('md_membership')->__('Membership Subscription Plan \'%s\'',$this->getPlan()->getTitle());
    }
    
    public function getPaymentMethodTitle()
    {
        $title = 'N/A';
        $paymentMethod = Mage::helper('md_membership')->getPaymentArray();
        if(isset($paymentMethod[$this->getSubscription()->getPaymentMethod()])){
            $title = $paymentMethod[$this->getSubscription()->getPaymentMethod()];
        }
        return $title;
    }
    
    public function getCancelButtonHtml()
    {
        return $this->getChildHtml('subscription_cancel_button');
    }
    
    public function getActivateButtonHtml()
    {
        return $this->getChildHtml('subscription_activate_button');
    }
    
    public function getSuspendButtonHtml()
    {
        return $this->getChildHtml('subscription_suspend_button');
    }
    
    public function getRenewButtonHtml()
    {
        return $this->getChildHtml('subscription_renew_button');
    }
}

