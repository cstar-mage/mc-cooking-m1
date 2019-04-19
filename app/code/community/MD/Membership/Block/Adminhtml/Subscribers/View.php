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
class MD_Membership_Block_Adminhtml_Subscribers_View extends Mage_Adminhtml_Block_Widget
{
    protected $_subscriber = null;
    protected $_helper = null;
    public function __construct() {
        parent::__construct();
        $subscriberId = $this->getRequest()->getParam('id');
        $this->_subscriber = Mage::getModel('md_membership/subscribers')->load($subscriberId);
        $this->_helper = Mage::helper('md_membership');
        $this->setTemplate('md/membership/subscribers/view.phtml');
    }
    
    public function getSubscriber()
    {
        return $this->_subscriber;
    }
    
    public function getPlan()
    {
        return $this->getSubscriber()->getPlan();
    }
    
    protected function _prepareLayout()
    {
        if($this->getSubscriber()->canActivateProfile() && $this->getSubscriber()->getPaymentMethod() != Mage_Paygate_Model_Authorizenet::METHOD_CODE){
            $this->setChild('subscription_activate_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => $this->_helper->__('Activate Profile'),
                    'class' => 'add',
                    'on_click' => 'confirmSetLocation(\'Are you sure want to activate this profile?\',\''.$this->getUrl('*/*/activeProfile',array('id'=>$this->getRequest()->getParam('id',null))).'\')'
                ))
        );
        }
        
        if($this->getSubscriber()->canCancelProfile()){
            $this->setChild('subscription_cancel_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => $this->_helper->__('Cancel Profile'),
                    'class' => 'add',
                    'on_click' => 'confirmSetLocation(\'Are you sure want to cancel this profile?\',\''.$this->getUrl('*/*/cancelProfile',array('id'=>$this->getRequest()->getParam('id',null))).'\')'
                ))
        );
        }
        
        if($this->getSubscriber()->canSuspendProfile() && $this->getSubscriber()->getPaymentMethod() != Mage_Paygate_Model_Authorizenet::METHOD_CODE){
            $this->setChild('subscription_suspend_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => $this->_helper->__('Suspend Profile'),
                    'class' => 'add',
                    'on_click' => 'confirmSetLocation(\'Are you sure want to cancel this profile?\',\''.$this->getUrl('*/*/suspendProfile',array('id'=>$this->getRequest()->getParam('id',null))).'\')'
                ))
        );
        }
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
    
    public function getTitle()
    {
        return $this->_helper->__('Subscriber # %s | %s', $this->getSubscriber()->getProfileId(), $this->formatDate($this->getSubscriber()->getCreatedAt(), 'medium', true));
    }
}

