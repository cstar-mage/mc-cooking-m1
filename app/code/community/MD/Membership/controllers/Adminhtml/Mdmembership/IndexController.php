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
class MD_Membership_Adminhtml_Mdmembership_IndexController extends Mage_Adminhtml_Controller_Action
{
    protected $_modelMap = array(
        Mage_Paygate_Model_Authorizenet::METHOD_CODE=>'md_membership/payment_paygate_authorizenet',
        Mage_Paypal_Model_Config::METHOD_WPP_EXPRESS=>'md_membership/payment_paypal_express'
    );
    
    public function indexAction()
    {
       
        $this->loadLayout();
        $this->_setActiveMenu('md_membership');
        $this->getLayout()->getBlock('head')->setTitle(Mage::helper('md_membership')->__('Membership Plans'));
        $this->renderLayout();
    }
    
    public function editAction()
    {
        $id = $this->getRequest()->getParam('id',null);
        $model = null;
        $planTitle = Mage::helper('md_membership')->__('Add New Membership Plan');
        if($id){
            $model = Mage::getModel('md_membership/plans')->load($id);
            $planTitle = $model->getData('title');
        }
        if(!is_null($model) || is_null($id)){
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if(!empty($data))
            {
                $model->setData($data);
            }
            Mage::register('membership_data',$model);
            $this->loadLayout();
            $this->_setActiveMenu('md_membership');
            //$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Assembly Manager'),Mage::helper('adminhtml')->__('Item Manager'));
			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
                        $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
                        $this->getLayout()->getBlock("head")->setTitle($planTitle);
            $this->_addContent($this->getLayout()->createBlock('md_membership/adminhtml_plans_edit'))
					->_addLeft($this->getLayout()->createBlock('md_membership/adminhtml_plans_edit_tabs'));

			$this->renderLayout();
        }else{
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('assembly')->__('Item does not exists'));
            $this->_redirect('*/*/');
        }
    }
    
    public function newAction()
    {
        $this->_forward('edit');
    }
    
    public function saveAction()
    {
        if ($this->getRequest()->getPost()) {
            $uploadPath = Mage::getBaseDir().DS.'media'.DS.'md'.DS.'membership'.DS.'plans'.DS;
            $posts = $this->getRequest()->getPost();
            
            $id = $this->getRequest()->getParam("id",null);
            $membershipData = $posts['membership'];
            
            if(isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
                
                 try{
                     $uploader = new Varien_File_Uploader('image');
                     $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png'));
                     $uploader->setAllowRenameFiles(true);
                     $uploader->setAllowCreateFolders(true);
                     $uploader->setFilesDispersion(false);
                     
                     $uploader->save($uploadPath, $_FILES['image']['name']);
                     $membershipData['image'] = $uploader->getUploadedFileName();
                     if(isset($id))
                    {
                       $image = Mage::getModel("md_membership/plans")->load($id)->getImage();
                       if(is_file($uploadPath.$image)){
                        unlink($uploadPath.$image);
                       }
                    }
                 }catch(Exception $e){
                        Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
                        $this->_redirect("*/*/");
                 }
                 }
                 if(isset($posts['image']['delete']) && $posts['image']['delete'] == 1){
                     $existingName = $posts['image']['value'];
                     if(is_file($uploadPath.$existingName)){
                         unlink($uploadPath.$existingName);
                     }
                     $membershipData['image'] = null;
                 }
                 try{
                     $model = Mage::getModel("md_membership/plans")->setData($membershipData)->setId($id)->save();
                        $PID = $model->getId();
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('md_membership')->__('Data successfully saved'));
                    Mage::getSingleton('adminhtml/session')->setFormData(false);

                    if ($this->getRequest()->getParam('back')) {
                        $this->_redirect('*/*/edit', array('id' => $PID));
                    }else{
                        $this->_redirect('*/*/');
                    }
                    
                 }catch(Exception $e){
                        Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
                        $this->_redirect("*/*/");
                 }
        }else{
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('md_membership')->__('Unable to find membership data to save'));
            $this->_redirect('*/*/');}
    }
    
    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam("membership");
        if(!is_array($ids))
        {
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("md_membership")->__("Please select any item to delete"));
        }
        else
        {
            try{
                foreach($ids as $id)
                {
                    $model = Mage::getModel("md_membership/plans")->load($id);
                    $hasActiveFlag = $model->hasActiveSubscribers();
                    if($hasActiveFlag){
                        $model->setStatus(MD_Membership_Model_Plans::SUBSCRIPTION_STATUS_ARCHIVED)->save();
                    }else{
                        $model->delete();
                    }
                }
                Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("md_membership")->__("Total %d item(s) are deleted successfully",count($ids)));
            }catch(Exception $e){
                Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
           }
        }
        $this->_redirect("*/*/index");
    }
    
    public function massStatusAction()
    {
        $ids = $this->getRequest()->getParam("membership");
        if(!is_array($ids))
        {
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("md_membership")->__("Please select any items to change status"));
        }
        else
        {
            try{
                foreach($ids as $id){
                    $status = $this->getRequest()->getParam("status");
                    $model = Mage::getModel("md_membership/plans")->load($id);
                    $model->setData("status",$status);
                    $model->save();                     
                }
                Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("md_membership")->__("Total %d items(s) updated successfully",count($ids)));
            }catch(Exception $e){
                Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
            }
        }
        $this->_redirect("*/*/index");
    }
    
    public function fetchSubscriptionsAction()
    {
        $planId = $this->getRequest()->getParam('plan_id',null);
        if($planId){
            
            $plan = Mage::getModel('md_membership/plans')->load($planId);
            $subscribersCollection = $plan->getSubscribersCollection();
            if(!is_null($subscribersCollection)){
                foreach($subscribersCollection as $_subscription){
                    $report = Mage::getModel($this->_modelMap[$_subscription->getPaymentMethod()])
                                    ->setSubscriber($_subscription);
                    
                    $response = $report->getSubscriptionStatus();
                    if(array_key_exists('subscriber_id',$response) && array_key_exists('status',$response) && array_key_exists('reference_id',$response)){
                        Mage::getModel('md_membership/subscribers')
                                        ->setStatus($response['status'])
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
                    }
                }
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper("md_membership")->__('Subscription data updated successfully.'));
            $this->_redirect('*/*/edit',array('id'=>$planId));
        }else{
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper("md_membership")->__('Subscription plan not found'));
            $this->_redirect('*/*/');
        }
    }
    
    public function massSubscribersStatusAction()
    {
        
        $ids = $this->getRequest()->getParam("subscribers");
        $planId = $this->getRequest()->getParam('plan_id',null);
        if(!is_array($ids))
        {
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("md_membership")->__("Please select any items to change status"));
        }
        else
        {
            try{
                foreach($ids as $id){
                    $status = $this->getRequest()->getParam("status");
                    $subscribers = Mage::getModel('md_membership/subscribers')->load($id);
                    $actionObj = Mage::getModel($this->_modelMap[$subscribers->getPaymentMethod()])
                                    ->setSubscriber($subscribers);
                    
                    $response = $actionObj->updateProfile($status);
                    
                    if(count($response) > 0 && array_key_exists('profile_id',$response) && array_key_exists('subscriber_id',$response))
                    {
                        $subscribers->setStatus($status)->setId($id)->save();
                    }        
                }
                Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("md_membership")->__("Total %d items(s) updated successfully",count($ids)));
            }catch(Exception $e){
                Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
            }
        }
        $this->_redirect("*/*/edit",array('id'=>$planId));
    }
    
    public function massSubscribersDelete()
    {
        $ids = $this->getRequest()->getParam("subscribers");
        $planId = $this->getRequest()->getParam('plan_id',null);
        if(!is_array($ids))
        {
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("md_membership")->__("Please select any items to change delete"));
        }
        else
        {
            try{
                $counter = 0;
                foreach($ids as $id){
                    $subscriber = Mage::getModel('md_membership/subscribers')->load($id);
                    if($subscriber->getStatus() == MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_CANCELLED){
                        $subscriber->delete();
                        $counter++;
                    }
                }
                if($counter > 0){
                    Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("md_membership")->__("Total %d items(s) subscribers whose status \'Canceled\' deleted successfully",count($counter)));
                }else{
                    Mage::getSingleton("adminhtml/session")->addError(Mage::helper("md_membership")->__("Subscribers not found whose status \'Canceled\'.Please select any subscriber whose status \'Canceled\' and try again."));
                }
            }catch(Exception $e){
                Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
            }
        }
        $this->_redirect("*/*/edit",array('id'=>$planId));
    }
    
    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id',null);
        
        if(!$id)
        {
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("md_membership")->__("Membership Plan does not exists"));
        }
        else
        {
            $model = Mage::getModel("md_membership/plans")->load($id);
            try{
                $hasActiveFlag = $model->hasActiveSubscribers();
                if($hasActiveFlag){
                    $model->setStatus(MD_Membership_Model_Plans::SUBSCRIPTION_STATUS_ARCHIVED)->save();
                }else{
                    $model->delete();
                }
                Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("md_membership")->__("Membership plan deleted successfully.")); 
            }catch(Exception $e){
                Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
            }
        }
        $this->_redirect("*/*/");
    }
}

