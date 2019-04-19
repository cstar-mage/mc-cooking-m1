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
class MD_Membership_Block_View extends Mage_Core_Block_Template
{
    public function __construct() {
        parent::__construct();
        $this->setTemplate('md/membership/view.phtml');
    }
    
    protected function _prepareLayout()
    {
        if($breadcrumbs = $this->getLayout()->getBlock('breadcrumbs'))
        {
            $helper = Mage::helper('md_membership');
            $plan = Mage::registry('current_membership_plan');
            if($plan){
                $breadcrumbs->addCrumb('home', array('label'=>Mage::helper('cms')->__('Home'), 'title'=>Mage::helper('cms')->__('Go to Home Page'), 'link'=>Mage::getBaseUrl()));
                $breadcrumbs->addCrumb('membership_list_page', array('label'=>$helper->getMembershipLinkTitle(), 'title'=>$helper->getMembershipLinkTitle(), 'link'=>$helper->getMembershipUrl()));
                $breadcrumbs->addCrumb('view', array('label'=>$plan->getTitle(), 'title'=>$plan->getTitle()));
            }
        }        
        return parent::_prepareLayout();
    }
    
    public function getMembershipPlan()
    {
        $plan = Mage::registry('current_membership_plan');
        if(!$plan){
            $id = $this->getRequest()->getParam('id',null);
            $plan = Mage::getModel('md_membership/plans')->load($id);
        }
        return $plan;
    }
    
    public function getTermsConditionText()
    {
        $text = Mage::getStoreConfig('md_membership/general/terms_condition_text',Mage::app()->getStore()->getId());
        if(strlen($text) > 0){
            return $text;
        }else{
            return null;
        }
    }
    
    protected function _toHtml()
    {
        $plan = $this->getMembershipPlan();
        $helper = Mage::helper('cms');
                $processor = $helper->getBlockTemplateProcessor();
                $description = $processor->filter($plan->getDescription());
        $this->setContentDescription($description);
        return parent::_toHtml();
    }
}

