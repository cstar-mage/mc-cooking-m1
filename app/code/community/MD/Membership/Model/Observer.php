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
class MD_Membership_Model_Observer
{
    public function addLinkToNavigation(Varien_Event_Observer $observer)
    {
        $menu = $observer->getEvent()->getMenu();
        $tree = $menu->getTree();
        $helper = Mage::helper('md_membership');
        if($helper->displayInTopNavigation()){
            $node = new Varien_Data_Tree_Node(array(
                'name'   => $helper->getMembershipLinkTitle(),
                'id'     => 'membership-nav',
                'url'    => $helper->getMembershipUrl(), // point somewhere
        ), 'id', $tree, $menu);

            $menu->addChild($node);
        }
    }
    
    public function addSubscriptionTab(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        $id = Mage::app()->getRequest()->getParam('id',null);
        if ($block instanceof Mage_Adminhtml_Block_Customer_Edit_Tabs) {
            if($id){
                $block->addTab('md_membership_subscriptions', array(
                    'label'     => Mage::helper('md_membership')->__('Membership Subscriptions'),
                    'title'       => Mage::helper('md_membership')->__('Membership Subscriptions'),
                    'content'     => $block->getLayout()->createBlock("md_membership/adminhtml_customer_edit_tab_subscriptions")->toHtml(),
                ));
            }
        }
    }
    
    public function fetchSubscriptionData()
    {
        $collection = Mage::getModel('md_membership/subscribers')->getCollection()
                                        ->addFieldToFilter('status',array('nin'=>array(MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_CANCELLED,MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_EXPIRED)));
        
        foreach($collection as $_subscriber){
            $report = null;
            switch($_subscriber->getPaymentMethod()){
                case Mage_Paypal_Model_Config::METHOD_WPP_EXPRESS:
                        $report = Mage::getModel('md_membership/payment_paypal_express')->setSubscriber($_subscriber);
                        break;
                case Mage_Paygate_Model_Authorizenet::METHOD_CODE:
                        $report = Mage::getModel('md_membership/payment_paygate_authorizenet')->setSubscriber($_subscriber);
                        break;
            }
            
            if(!is_null($report)){
                $response = $report->getSubscriptionStatus();
                $isStatusChanged = false;
                if(array_key_exists('subscriber_id',$response) && array_key_exists('status',$response) && array_key_exists('reference_id',$response)){
                    $subscriber =  Mage::getModel('md_membership/subscribers')->load($response['subscriber_id']);
                    if($subscriber->getStatus() != $response['status']){
                        $isStatusChanged = true;
                    }
                                        $subscriber->setStatus($response['status'])
                                        ->setNextBillingDate($response['next_billing_date'])
                                        ->setLastPaymentDate($response['last_payment_date'])
                                        ->setBillingCyclesCompleted($response['billing_cycles_completed'])
                                        ->setBillingCyclesRemains($response['billing_cycles_remains'])
                                        ->setRegularBillingCycles($response['regular_billing_cycles'])
                                        ->setTrialBillingCycles($response['trial_billing_cycles'])
                                        ->setPaymentFailedCount($response['payment_failed_count'])
                                        ->setFinalPaymentDate($response['final_payment_date'])
                                        ->setId($response['subscriber_id'])
                                        ->save();
                        if($isStatusChanged){
                            Mage::helper('md_membership')->sendSubscriptionStatusEmail($subscriber);
                        }
                    }
            }
        }
        return $this;
    }
    
    public function insertSummary(Varien_Event_Observer $observer){
        $paypalResponse = $observer->getEvent()->getPaypalResponse();
        $lastPaymentDate = (array_key_exists('LASTPAYMENTDATE',$paypalResponse) && strlen($paypalResponse['LASTPAYMENTDATE'])) ? date('Y-m-d',strtotime($paypalResponse['LASTPAYMENTDATE'])): null;
        $nextPaymentDate =(array_key_exists('NEXTBILLINGDATE',$paypalResponse) && strlen($paypalResponse['NEXTBILLINGDATE'])) ? date('Y-m-d',strtotime($paypalResponse['NEXTBILLINGDATE'])): null;
        $subscriber = $observer->getEvent()->getSubscriber();
        $payment = null;
        if(!is_null($lastPaymentDate) && !is_null($nextPaymentDate)){
        $existingCollection = Mage::getModel('md_membership/payments')
                                    ->getCollection()
                                    ->addFieldToFilter('last_payment',array('eq'=>$lastPaymentDate))
                                    ->addFieldToFilter('	profile_id',array('eq'=>$paypalResponse['PROFILEID']));
        
        if($existingCollection->count() > 0){
            $payment = $existingCollection->getFirstItem();
                    $payment->setProfileId($paypalResponse['PROFILEID'])
                            ->setReferenceId($subscriber->getReferenceId())
                            ->setGrossAmount($paypalResponse['AGGREGATEAMOUNT'])
                            ->setDueAmount($paypalResponse['OUTSTANDINGBALANCE'])
                            ->setLastPayment($lastPaymentDate)
                            ->setLastPaidAmount($paypalResponse['LASTPAYMENTAMT'])
                            ->setNextPayment($nextPaymentDate)
                            ->setAdditionalInfo(serialize($paypalResponse))
                            ->setId($payment->getId());
        }else{
            $payment = Mage::getModel('md_membership/payments')
                            ->setProfileId($paypalResponse['PROFILEID'])
                            ->setReferenceId($subscriber->getReferenceId())
                            ->setGrossAmount($paypalResponse['AGGREGATEAMOUNT'])
                            ->setDueAmount($paypalResponse['OUTSTANDINGBALANCE'])
                            ->setLastPayment(date('Y-m-d',strtotime($paypalResponse['LASTPAYMENTDATE'])))
                            ->setLastPaidAmount($paypalResponse['LASTPAYMENTAMT'])
                            ->setNextPayment(date('Y-m-d',strtotime($paypalResponse['NEXTBILLINGDATE'])))
                            ->setAdditionalInfo(serialize($paypalResponse));
        }
        $payment->save();
        }
        return $this;
    }
    
    public function insertAuthorizeSummary(Varien_Event_Observer $observer)
    {
        $response = $observer->getEvent()->getRelayResponse();
        $isFailed = true;
        switch($response['x_response_code']){
            case '1':
                    $isFailed = false;
                    break;
            case '2':
                    $isFailed = true;
                    break;
            case '3':
                    $isFailed = true;
                    break;
            default:
                    $isFailed = true;
                    break;
        }
        $text = $response['x_response_reason_text'];
        $helper = Mage::helper('md_membership');
        $lastPaymentDate = date("Y-m-d",Mage::getSingleton('core/date')->gmtTimestamp());
        $collection = Mage::getModel('md_membership/subscribers')->getCollection()
                                    ->addFieldToFilter('profile_id',array('eq'=>$response['x_subscription_id']));
        if($collection->count() > 0){
            $subscriber = $collection->getFirstItem();
            $existingCollection = Mage::getModel('md_membership/payments')
                                    ->getCollection()
                                    ->addFieldToFilter('last_payment',array('eq'=>$lastPaymentDate))
                                    ->addFieldToFilter('	profile_id',array('eq'=>$response['x_subscription_id']));
            if($existingCollection->count() > 0){
                $payment = $existingCollection->getFirstItem();
                $payment->setProfileId($response['x_subscription_id'])
                            ->setReferenceId($subscriber->getReferenceId())
                            ->setGrossAmount(0)
                            ->setDueAmount(0)
                            ->setLastPayment($lastPaymentDate)
                            ->setLastPaidAmount($response['x_amount'])
                            ->setNextPayment(null)
                            ->setAdditionalInfo(serialize($response))
                            ->setId($payment->getId());
                if(!$payment->getEmailSent()){
                    $sent = $helper->sendPaymentStatusEmail($subscriber,$payment,$isFailed,$text);
                    $payment->setEmailSent($sent);
                }
                
            }else{
                $payment = Mage::getModel('md_membership/payments')
                            ->setProfileId($response['x_subscription_id'])
                            ->setReferenceId($subscriber->getReferenceId())
                            ->setGrossAmount(0)
                            ->setDueAmount(0)
                            ->setLastPayment($lastPaymentDate)
                            ->setLastPaidAmount($response['x_amount'])
                            ->setNextPayment(null)
                            ->setAdditionalInfo(serialize($response));
                if(!$payment->getEmailSent()){
                    $sent = $helper->sendPaymentStatusEmail($subscriber,$payment,$isFailed,$text);
                    $payment->setEmailSent($sent);
                }
            }
            $payment->save();
        }
        return $this;
    }
    
    public function checkSubscriberStoreview(Varien_Event_Observer $observer)
        {  
            $Action=$observer->getEvent()->getControllerAction()->getFullActionName();            
            $planid= Mage::app()->getRequest()->getParam('id');               
            if(!empty($planid) && $Action=="md_membership_index_view"){
                $planstore=Mage::getModel("md_membership/plans")->load($planid)->getStoreIds();
                if($planstore!=0){
                    $currentstore=Mage::app()->getStore()->getStoreId();
                    $redirectcheck=strpos($planstore,',') !== false ? in_array($currentstore,explode(",",$planstore)): $currentstore==$planstore;    
                    if($redirectcheck==false){     
                        Mage::getSingleton('core/session')->addError(Mage::helper('md_membership')->__('This plan is not available for current store.'));        
                        $controller = $observer->getEvent()->getControllerAction();
                        $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                        $controller->getResponse()->setRedirect(Mage::helper('md_membership')->getMembershipUrl());
                    }
                }
            }
            $summaryid=Mage::app()->getRequest()->getParam('plan');            
            if($Action=="md_membership_index_payment" && empty($summaryid)){
                Mage::getSingleton('core/session')->addError(Mage::helper('md_membership')->__('This plan is not available for current store.'));        
                $controller = $observer->getEvent()->getControllerAction();
                $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                $controller->getResponse()->setRedirect(Mage::helper('md_membership')->getMembershipUrl());
            }
        }
}

