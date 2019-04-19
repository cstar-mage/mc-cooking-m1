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
class MD_Membership_Block_Customer_Subscriptions_List extends Mage_Core_Block_Template
{
    protected $_customer = null;
    public function __construct()
    {
        parent::__construct();
        $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        $this->setTemplate('md/membership/customer/subscriptions/list.phtml');
        $this->setSubscribedPlans(Mage::getModel('md_membership/subscribers')->getMembershipByCustomer($this->_customer));
    }
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if($this->getSubscribedPlans()){
            $pager = $this->getLayout()->createBlock('page/html_pager', 'md.membership.subscriptions.pager')
                ->setCollection($this->getSubscribedPlans());
            $this->setChild('pager', $pager);
            $this->getSubscribedPlans()->load();
        }
        return $this;
    }
    
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }
    
    public function getViewUrl($subscription)
    {
        return $this->getUrl('*/*/view', array('subscription_id' => $subscription->getId()));
    }
}

