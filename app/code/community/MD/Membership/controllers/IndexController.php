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
class MD_Membership_IndexController extends Mage_Core_Controller_Front_Action
{
    protected $_creditCardRequiredMethod = array(
        Mage_Paygate_Model_Authorizenet::METHOD_CODE
    );
    
    protected $_modelMap = array(
        Mage_Paygate_Model_Authorizenet::METHOD_CODE=>'md_membership/payment_paygate_authorizenet',
        Mage_Paypal_Model_Config::METHOD_WPP_EXPRESS=>'md_membership/payment_paypal_express'
    );
    
    protected $_redirectMethods = array(
        Mage_Paypal_Model_Config::METHOD_WPP_EXPRESS
    );
    
    protected $_redirectAction = array(
        Mage_Paypal_Model_Config::METHOD_WPP_EXPRESS => '*/*/paypalRedirect'
    );
    
    public function successAction()
    {
        $token = $this->getRequest()->getParam('token');
        $planId = Mage::getSingleton('md_membership/session')->getPlanId();
        $plan = Mage::getModel('md_membership/plans')->load($planId);
        $arrayData = $plan->getData();
        $arrayData['plan_url'] = $plan->getPlanUrl();
            $arrayData['image_url'] = $plan->getImageUrl();
        $addressId = Mage::getSingleton('md_membership/session')->getCustomerAddressId();
        $subscriptionDate = Mage::getSingleton('md_membership/session')->getSubscriptionStartDate();
        
        $payment = Mage::getModel('md_membership/payment_paypal_express')
                                ->setMembershipPlanId($planId)
                                ->setBillingAddressId($addressId)
                                ->setSubscriptionStartDate($subscriptionDate);
        
        $result = $payment->getExpressCheckoutDetails($token);
        if(array_key_exists('token',$result) && array_key_exists('payer_id',$result)){
            $profile = $payment->requestRecurringProfile($result['token'],$result['payer_id']);
            
            if(array_key_exists('profile_id',$profile)){
                $profile['reference_id'] = Mage::getModel('md_membership/subscribers')->getReservedIncrementId();
                $profile['plan_data'] = serialize($arrayData);
                $subscribers = Mage::getModel('md_membership/subscribers')
                                ->setData($profile);
                $subscribers->save();
                Mage::helper('md_membership')->sendNewSubscriptionEmail($subscribers);
                Mage::getSingleton('core/session')->addSuccess(Mage::helper('md_membership')->__('You are subscribed for membership plan \'%s\'',$plan->getTitle()));
            }else{
                Mage::getSingleton('core/session')->addError(Mage::helper('md_membership')->__($profile['error']));
            }
        }else{
            Mage::getSingleton('core/session')->addError(Mage::helper('md_membership')->__($result['error']));
        }
        Mage::getSingleton('md_membership/session')->unsSubscriptionStartDate();
        Mage::getSingleton('md_membership/session')->unsPlanId();
        Mage::getSingleton('md_membership/session')->unsCustomerAddressId();
        $this->getResponse()->setRedirect($plan->getPlanUrl());
    }
    
    public function cancelAction()
    {
        $token = $this->getRequest()->getParam('token');
        $planId = Mage::getSingleton('md_membership/session')->getPlanId();
        $plan = Mage::getModel('md_membership/plans')->load($planId);
        Mage::getSingleton('core/session')->addError(Mage::helper('md_membership')->__('Error occured during initiating payment for membership plan \'%s\'.',$plan->getTitle()));
        Mage::getSingleton('md_membership/session')->unsSubscriptionStartDate();
        Mage::getSingleton('md_membership/session')->unsPlanId();
        Mage::getSingleton('md_membership/session')->unsCustomerAddressId();
        $this->getResponse()->setRedirect($plan->getPlanUrl());
    }
    
    public function listAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
    
    public function viewAction()
    {
        $id = $this->getRequest()->getParam('id',null);
        $membershipPlan = null;
        if($id){
            $membershipPlan = Mage::getModel('md_membership/plans')->load($id);
        }
        Mage::register('current_membership_plan',$membershipPlan);
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($membershipPlan->getTitle());
        $this->renderLayout();        
    }
    
    public function paymentAction()
    {
        $params = $this->getRequest()->getParams();
        if(Mage::getSingleton('customer/session')->isLoggedIn()){
            $planId = $params['plan'];
            
            $plan = Mage::getSingleton('md_membership/plans')->load($planId);
            if(isset($params['membership']['subscription_start_date']) && !is_null($params['membership']['subscription_start_date'])){
                $plan->setData('customer_subscription_Date',$params['membership']['subscription_start_date']);
            }
            Mage::register('current_plan',$plan);
            $this->loadLayout();
            $this->renderLayout();
        }else{
            Mage::getSingleton('core/session')->addError(Mage::helper('md_membership')->__('Please login to buy membership plans.'));
            Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::helper('core')
                ->urlDecode($params[Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED]));
            $this->_redirect('customer/account/login');
        }
    }
    
    public function payAction(){
        if($this->getRequest()->isXmlHttpRequest()){
            
            $posts = $this->getRequest()->getPost();
            $billingAddressId = (isset($posts['billing_address_id']) && !is_null($posts['billing_address_id'])) ? $posts['billing_address_id']: null;
            $planId = $this->getRequest()->getParam('plan_id',null);
            $method = $posts['membership']['method'];
            $cardDetails = $posts[$method];
            $paymentModelClass = (isset($this->_modelMap[$method])) ? $this->_modelMap[$method]: null;
            Mage::getSingleton('md_membership/session')->setPlanId($planId);
            Mage::getSingleton('md_membership/session')->setCustomerAddressId($billingAddressId);
            Mage::getSingleton('md_membership/session')->setSubscriptionStartDate($posts['subscription_date']);
            if($paymentModelClass && $planId){
                $payment = Mage::getModel($paymentModelClass)
                                ->setMembershipPlanId($planId)
                                ->setBillingAddressId($billingAddressId)
                                
                                ->setSubscriptionStartDate($posts['subscription_date']);
                if(in_array($method,$this->_creditCardRequiredMethod)){
                    $response = $payment->setCardDetails($cardDetails)->pay();
                    $result = array();
                    if(array_key_exists('profile_id',$response))
                    {   
                        $plan = Mage::getModel('md_membership/plans')->load($planId);
                        $arrayData = $plan->getData();
                        $arrayData['plan_url'] = $plan->getPlanUrl();
                        $arrayData['image_url'] = $plan->getImageUrl();
                        if(!array_key_exists('reference_id',$response)){
                            $response['reference_id'] = Mage::getModel('md_membership/subscribers')->getReservedIncrementId();
                        }
                        $response['plan_data'] = serialize($arrayData);
                        $subscribers = Mage::getModel('md_membership/subscribers')
                            ->setData($response);
                        $subscribers->save();
                        Mage::helper('md_membership')->sendNewSubscriptionEmail($subscribers);
                        Mage::getSingleton('core/session')->addSuccess(Mage::helper('md_membership')->__('You are subscribed for membership plan \'%s\'',$response['plan_title']));
                        $result['redirect_url'] = $response['redirect_url'];
                        
                    }else{
                        $result['error'] = $response['error'];
                    }
                    
                }else{
                    $result = $payment->callSetExpressCheckoutMethod();
                }
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
        
    }
    
    public function expressRedirect(){
        $this->getResponse()->setBody($this->getLayout()->createBlock('md_membership/paypal_express')->toHtml());
    }
    
    public function relayResponseAction()
    {
        $params = $this->getRequest()->getParams();
        $posts = $this->getRequest()->getPosts();
 
        return false;
    }
}
