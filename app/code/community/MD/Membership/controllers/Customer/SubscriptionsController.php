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
class MD_Membership_Customer_SubscriptionsController extends Mage_Core_Controller_Front_Action
{
    protected $_modelMap = array(
        Mage_Paygate_Model_Authorizenet::METHOD_CODE=>'md_membership/payment_paygate_authorizenet',
        Mage_Paypal_Model_Config::METHOD_WPP_EXPRESS=>'md_membership/payment_paypal_express'
    );
    public function preDispatch()
    {
         parent::preDispatch();
         $loginUrl = Mage::helper('customer')->getLoginUrl();

            if (!Mage::getSingleton('customer/session')->authenticate($this, $loginUrl)) {
                $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            }
            return $this;
    }
    
    public function listAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function viewAction()
    {
        if (!$this->_loadValidSubscriptions()) {
            return;
        }

        $this->loadLayout();
        $this->_initLayoutMessages('catalog/session');

        $navigationBlock = $this->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('md_membership/customer_subscriptions/list');
        }
        $this->renderLayout();
    }
    
    protected function _loadValidSubscriptions($subscriptionId = null)
    {
		$customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
        if (null === $subscriptionId) {
            $subscriptionId = (int) $this->getRequest()->getParam('subscription_id',null);
        }
        if (!$subscriptionId) {
            $this->_forward('noRoute');
            return false;
        }

        $subscription = Mage::getModel('md_membership/subscribers')->load($subscriptionId);

        if ($subscription->getId() && $customerId == $subscription->getCustomerId()) {
            return true;
        } else {
            $this->_redirect('*/*/list');
        }
        return false;
    }
    
    public function activeProfileAction()
    {
        $subscriberId = $this->getRequest()->getParam('id',null);
        $status = MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_ACTIVE;
        $subscriber = Mage::getModel('md_membership/subscribers')->load($subscriberId);
                    $actionObj = Mage::getModel($this->_modelMap[$subscriber->getPaymentMethod()])
                                    ->setSubscriber($subscriber);
                    
                    $response = $actionObj->updateProfile($status);
                    
                    if(count($response) > 0 && array_key_exists('profile_id',$response) && array_key_exists('subscriber_id',$response))
                    {
                        $subscriber->setStatus($status)->setId($subscriberId)->save();
                        Mage::getSingleton('core/session')->addSuccess(Mage::helper("md_membership")->__("Profile activated Successfully."));
                    }else{
                        Mage::getSingleton('core/session')->addError(Mage::helper("md_membership")->__("Profile has not activated due to some error."));
                    }
        $this->_redirect("*/*/view",array('subscription_id'=>$subscriberId));            
    }
    
    public function cancelProfileAction()
    {
        $subscriberId = $this->getRequest()->getParam('id',null);
        $status = MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_CANCELLED;
        $subscriber = Mage::getModel('md_membership/subscribers')->load($subscriberId);
                    $actionObj = Mage::getModel($this->_modelMap[$subscriber->getPaymentMethod()])
                                    ->setSubscriber($subscriber);
                    
                    $response = $actionObj->updateProfile($status);
                    
                    if(count($response) > 0 && array_key_exists('profile_id',$response) && array_key_exists('subscriber_id',$response))
                    {
                        $subscriber->setStatus($status)->setId($subscriberId)->save();
                        Mage::getSingleton('core/session')->addSuccess(Mage::helper("md_membership")->__("Profile canceled Successfully."));
                    }else{
                        Mage::getSingleton('core/session')->addError(Mage::helper("md_membership")->__("Profile has not canceled due to some error."));
                    }
        $this->_redirect("*/*/view",array('subscription_id'=>$subscriberId));
    }
    
    public function suspendProfileAction()
    {
        
        $subscriberId = $this->getRequest()->getParam('id',null);
        
        $status = MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_SUSPENDED;
        $subscriber = Mage::getModel('md_membership/subscribers')->load($subscriberId);
                    $actionObj = Mage::getModel($this->_modelMap[$subscriber->getPaymentMethod()])
                                    ->setSubscriber($subscriber);
                    
                    $response = $actionObj->updateProfile($status);
                    
                    if(count($response) > 0 && array_key_exists('profile_id',$response) && array_key_exists('subscriber_id',$response))
                    {
                        $subscriber->setStatus($status)->setId($subscriberId)->save();
                        Mage::getSingleton('core/session')->addSuccess(Mage::helper("md_membership")->__("Profile suspended Successfully."));
                    }else{
                        Mage::getSingleton('core/session')->addError(Mage::helper("md_membership")->__("Profile has not suspended due to some error."));
                    }
        $this->_redirect("*/*/view",array('subscription_id'=>$subscriberId));
    }
    
}