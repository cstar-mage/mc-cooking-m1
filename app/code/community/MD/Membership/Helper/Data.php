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
class MD_Membership_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_layouts = array(
                            "empty"=>"page/empty.phtml",
                            "one_column"=>"page/1column.phtml",
                            "two_columns_left"=>"page/2columns-left.phtml",
                            "two_columns_right"=>"page/2columns-right.phtml",
                            "three_columns"=>"page/3columns.phtml"
                        );
    
    const MEMBERSHIP_ALLOWED_PAYMENT_METHODS = 'global/md_membership_validator/allowed/payment/methods';
    public function displayInTopLinks()
    {
        $config = Mage::getStoreConfig('md_membership/membership_list/link_section');
        return ($config === 1);
    }
    
    public function getCheckversion($version,$operator = null){
        $currentVersion = Mage::getVersion();
        
        $result = version_compare($currentVersion,$version,$operator);
        
        return $result;
    }
    
    public function getResizedImage($imageName,$width=null,$height=null)
    {
        $folderPath = Mage::getBaseDir().DS.'media'.DS.'md'.DS.'membership'.DS.'plans'.DS;
        $containerFolder = 'plans';
        $origionalImagePath = $folderPath.$imageName;
        if(!is_file($origionalImagePath)){
           $folderPath = Mage::getBaseDir().DS.'media'.DS.'md'.DS.'membership'.DS.'placeholder'.DS;
           $imageName = Mage::getStoreConfig('md_membership/general/image_placeholder',Mage::app()->getStore()->getId());
           if(strlen($imageName) > 0){
               $origionalImagePath = $folderPath.str_replace('/',DS,$imageName);
           }else{
              $origionalImagePath = Mage::getBaseDir().DS.'skin'.DS.'frontend'.DS.'base'.DS.'default'.DS.'images'.DS.'catalog'.DS.'product'.DS.'placeholder'.DS.'image.jpg';
              $imageName = 'image.jpg';
           }
           $containerFolder = 'placeholder';
        }
        if(is_null($width) && is_null($height)){
            $width = 100;
            $height = 100;
        }
        $resizePath = $width.'x'.$height;
        $resizePathFull = $folderPath.$resizePath.DS.$imageName;
        if (file_exists($origionalImagePath) && !file_exists($resizePathFull)) {
            $imageObj = new Varien_Image($origionalImagePath);
            $imageObj->constrainOnly(true);
            $imageObj->keepAspectRatio(false);
            $imageObj->resize($width,$height);
            $imageObj->save($resizePathFull);
        }
        return str_replace('index.php/','',Mage::getUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'md/membership/'.$containerFolder.'/'.$resizePath.'/'.$imageName);
    }
    
    public function displayInTopNavigation()
    {
        $config = Mage::getStoreConfig('md_membership/membership_list/link_section');
        return ($config == '0');
    }
    
    public function getMembershipLinkTitle()
    {
        $title = Mage::getStoreConfig('md_membership/membership_list/link_title');
        if(!$title){
            $title = $this->__('Membership');
        }
        return $title;
    }
    
    public function getMembershipLinkHeading()
    {
        $heading = Mage::getStoreConfig('md_membership/membership_list/heading_title');
        if(!$heading){
            $heading = $this->__('Membership Plans');
        }
        return $heading;
    }
    
    public function getMembershipUrl($ignoreBaseUrl = false)
    {
        $urlKey = trim(Mage::getStoreConfig("md_membership/membership_list/url_key"),'/');
        $suffix = trim(Mage::getStoreConfig("md_membership/membership_list/url_suffix"),'/');
        $urlKey .= (strlen($suffix) > 0 || $suffix != '') ? '.'.str_replace('.','',$suffix): '/';
        
        if(!$ignoreBaseUrl){
            return Mage::getUrl().$urlKey;
        }else{
            return $urlKey;
        }
    }
    
    public function getMembershipPageLayout()
    {
        return $this->_layouts[Mage::getStoreConfig('md_membership/membership_list/page_layout')];
    }
    
    public function getMembershipPlanAddUrl($plan){
        $routeParams = array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure());
        if((!class_exists('Enterprise_Reward_Helper_Data') && Mage::helper('md_membership')->getCheckversion("1.7.0.0",">=")) || (class_exists('Enterprise_Reward_Helper_Data') && Mage::helper('md_membership')->getCheckversion("1.13.0.0",">="))){
            $routeParams = array(
                Mage_Core_Model_Url::FORM_KEY => Mage::getSingleton('core/session')->getFormKey(),
		'_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()
            );
        }
        return Mage::getUrl('md_membership/index/payment', $routeParams);
    }
    
    public function getSubscriptionPlanLabel($key = null)
    {
        $_periodLable = array(
                MD_Membership_Model_Plans::BILLING_PERIOD_DAILY=>$this->__('Daily'),
                MD_Membership_Model_Plans::BILLING_PERIOD_WEEKLY=>$this->__('Weekly'),
                MD_Membership_Model_Plans::BILLING_PERIOD_MONTHLY=>$this->__('Monthly'),
                MD_Membership_Model_Plans::BILLING_PERIOD_BIMONTHLY=>$this->__('Bi Monthly'),
                MD_Membership_Model_Plans::BILLING_PERIOD_QUARTERLY=>$this->__('Quarterly'),
                MD_Membership_Model_Plans::BILLING_PERIOD_YEARLY=>$this->__('Yearly')
            );
        if(!is_null($key)){
            return $_periodLable[$key];
        }else{
            return $_periodLable;
        }
        
    }
    
    public function isAllowedMethod($code){
        $node = Mage::getConfig()->getNode(self::MEMBERSHIP_ALLOWED_PAYMENT_METHODS);
        
        if(!$node){
            $methods = array();
        }else{
            $methods = array_keys((array) $node);
        }
        if(in_array($code, $methods)){
            
            return true;
        }
        
        return false;
    }
    
    public function getPaymentArray($allowed = false)
    {
        $methods = Mage::getSingleton('payment/config')->getActiveMethods();
        $data = array();
        foreach($methods as $code=>$method){
            $data[$code] = $method->getTitle();
        }
        return $data;
    }
    
    public function getStatusLabels()
    {
        return array(
            0=>$this->__('Pending'),
            1=>$this->__('Active'),
            2=>$this->__('Expired'),
            3=>$this->__('Suspended'),
            4=>$this->__('Cancelled'),
            5=>$this->__('Terminated'),
        );
    }
    
    public function getStatusColorCodes()
    {
        return array(
            0=>$this->__('#FF9C00'),
            1=>$this->__('#3CB861'),
            2=>$this->__('#E41101'),
            3=>$this->__('#F55600'),
            4=>$this->__('#F55600'),
            5=>$this->__('#F55600'),
        );
    }
    
    public function sendNewSubscriptionEmail(MD_Membership_Model_Subscribers $subscriber)
    {
        if($subscriber instanceof MD_Membership_Model_Subscribers){
            //$subscriber = $subscriber->getData();
            $plan = $subscriber->getPlan()->getData();
            if(array_key_exists('status',$plan)){
                unset($plan['status']);
            }
            $templateParams = array_merge($subscriber->getData(),$plan);
            $templateParams['amount'] = Mage::helper('core')->currencyByStore($templateParams['amount'],$templateParams['store_id'],true,false);
            $templateParams['trial_amount'] = Mage::helper('core')->currencyByStore($templateParams['trial_amount'],$templateParams['store_id'],true,false);
            $templateParams['billing_period'] = Mage::helper('md_membership')->getSubscriptionPlanLabel($templateParams['billing_period']);
            $templateParams['status'] = $subscriber->getStatusLabel();
            $templateParams['payment_method'] = $subscriber->getPaymentMethodLabel();
            $templateParams['billing_address'] = $subscriber->getBillingAddressFormated();
            $templateParams['plan_url'] = $subscriber->getPlan()->getPlanUrl();
            $templateParams['profile_start_date'] = Mage::helper('core')->formatDate($templateParams['profile_start_date'],'medium');
            $templateParams['created_at'] = Mage::helper('core')->formatDate($templateParams['created_at'],'medium');
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            $mailTemplate = Mage::getModel('core/email_template');
            $template = (!Mage::getStoreConfig('md_membership/email/subscription_create',$subscriber->getStoreId())) ? 'md_membership_email_subscription_create' : Mage::getStoreConfig('md_membership/email/subscription_create',$subscriber->getStoreId());
            $bccConfig = Mage::getStoreConfig('md_membership/email/create_copy_to',$subscriber->getStoreId());
            $bcc = ($bccConfig) ? explode(",",$bccConfig): array();
            $sendTo = array(
                    array(
                    'email' => $subscriber->getEmail(),
                    'name'  => $subscriber->getName()
                )
            );
            foreach ($sendTo as $recipient) {
                if(count($bcc) > 0){
                    foreach($bcc as $copyTo){
                        $mailTemplate->addBcc($copyTo); 
                    }
                }
                $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$subscriber->getStoreId()))
                             ->sendTransactional(
                                $template,
                                Mage::getStoreConfig('md_membership/email/subscription_email_from',$subscriber->getStoreId()),
                                $recipient['email'],
                                $recipient['name'],
                                $templateParams
                            );
            }
            $translate->setTranslateInline(true);
        }
        return $this;
    }
    
    public function sendSubscriptionStatusEmail(MD_Membership_Model_Subscribers $subscriber){
        if($subscriber instanceof MD_Membership_Model_Subscribers){
            
            $templateParams = array();
            $templateParams['status'] = $subscriber->getStatusLabel();
            $templateParams['reference_id'] = $subscriber->getReferenceId();
            $templateParams['title'] = $subscriber->getPlan()->getTitle();
            $templateParams['name'] = $subscriber->getName();
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            $mailTemplate = Mage::getModel('core/email_template');
            $template = (!Mage::getStoreConfig('md_membership/email/subscription_status',$subscriber->getStoreId())) ? 'md_membership_email_subscription_status' : Mage::getStoreConfig('md_membership/email/subscription_status',$subscriber->getStoreId());
            $bccConfig = Mage::getStoreConfig('md_membership/email/status_copy_to',$subscriber->getStoreId());
            $bcc = ($bccConfig) ? explode(",",$bccConfig): array();
            $sendTo = array(
                    array(
                    'email' => $subscriber->getEmail(),
                    'name'  => $subscriber->getName()
                )
            );
            foreach ($sendTo as $recipient) {
                if(count($bcc) > 0){
                    foreach($bcc as $copyTo){
                        $mailTemplate->addBcc($copyTo); 
                    }
                }
                $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$subscriber->getStoreId()))
                             ->sendTransactional(
                                $template,
                                Mage::getStoreConfig('md_membership/email/subscription_email_from',$subscriber->getStoreId()),
                                $recipient['email'],
                                $recipient['name'],
                                $templateParams
                            );
            }
            $translate->setTranslateInline(true);
        }
        return $this;
    }
    
    public function sendPaymentStatusEmail(MD_Membership_Model_Subscribers $subscriber,MD_Membership_Model_Payments $payment,$isFailed=false,$text=null){
        $sendSuccess = 0;
        if($subscriber instanceof MD_Membership_Model_Subscribers && $payment instanceof MD_Membership_Model_Payments){
            $templateParams = array();
            $templateParams['store_id'] = $subscriber->getStoreId();
            $templateParams['name'] = $subscriber->getName();
            $templateParams['reference_id'] = $subscriber->getReferenceId();
            $templateParams['title'] = $subscriber->getPlan()->getTitle();
            $templateParams['plan_url'] = $subscriber->getPlan()->getPlanUrl();
            $templateParams['amount'] = Mage::helper('core')->currencyByStore($subscriber->getPlan()->getAmount(),$templateParams['store_id'],true,false);
            $templateParams['payment_date'] = Mage::helper('core')->formatDate($payment->getLastPayment(),'medium');
            $templateParams['processed_amount'] = Mage::helper('core')->currencyByStore($payment->getLastPaidAmount(),$templateParams['store_id'],true,false);;
            $templateParams['payment_status'] = ($isFailed) ? 'Failed': 'Paid';
            $templateParams['payment_method'] = $subscriber->getPaymentMethodLabel();
            $templateParams['status_text'] = $text;
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            $mailTemplate = Mage::getModel('core/email_template');
            $template = (!Mage::getStoreConfig('md_membership/email/payment_status',$templateParams['store_id'])) ? 'md_membership_email_payment_status' : Mage::getStoreConfig('md_membership/email/payment_status',$templateParams['store_id']);
            $bccConfig = Mage::getStoreConfig('md_membership/email/payment_status_copy_to',$templateParams['store_id']);
            $bcc = ($bccConfig) ? explode(",",$bccConfig): array();
            $sendTo = array(
                    array(
                    'email' => $subscriber->getEmail(),
                    'name'  => $subscriber->getName()
                )
            );
            foreach ($sendTo as $recipient) {
                if(count($bcc) > 0){
                    foreach($bcc as $copyTo){
                        $mailTemplate->addBcc($copyTo); 
                    }
                }
                $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$templateParams['store_id']))
                             ->sendTransactional(
                                $template,
                                Mage::getStoreConfig('md_membership/email/subscription_email_from',$templateParams['store_id']),
                                $recipient['email'],
                                $recipient['name'],
                                $templateParams
                            );
                $sendSuccess = ($mailTemplate->getSentSuccess()) ? 1: 0;
            }
            $translate->setTranslateInline(true);
        }
        return $sendSuccess;
    }
}

