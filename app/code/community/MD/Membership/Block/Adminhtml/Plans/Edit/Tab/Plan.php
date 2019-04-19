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
class MD_Membership_Block_Adminhtml_Plans_Edit_Tab_Plan extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm() {
        $helper = Mage::helper('md_membership');
        $membershipData = null;
        $dayOfMonthDisabled = false;
        $dayOfMonthClass = 'validate-digits-range digits-range-1-31 input-text';
        if (Mage::getSingleton('adminhtml/session')->getMembershipData()) {
            $membershipData = Mage::getSingleton('adminhtml/session')->getMembershipData();
        } elseif (Mage::registry('membership_data')) {
            $membershipData = Mage::registry('membership_data')->getData();
        }
        
        $dayOfMonthDisabled = ($membershipData['start_date_defined'] == MD_Membership_Model_Plans::DEFINED_DAY_OF_MONTH) ? false: true;
        $dayOfMonthClass = ($membershipData['start_date_defined'] == MD_Membership_Model_Plans::DEFINED_DAY_OF_MONTH) ? 'validate-digits-range digits-range-1-31 input-text': 'input-text disabled';
        
        
        
        $totalOccurencesDisabled = ($membershipData['is_limited'] != MD_Membership_Model_Plans::SUBSCRIPTION_LIFETIME) ? true: false;
        $totalOccurencesClass = ($membershipData['is_limited'] != MD_Membership_Model_Plans::SUBSCRIPTION_LIFETIME) ? 'validate-not-negative-number validate-greater-than-zero': 'input-text disabled';
        
        $form = new Varien_Data_Form();
        $this->setForm($form);
        
        $fieldset = $form->addFieldset('membership_plan_info', array('legend' => $helper->__('Membership Plans General Information')));
        $fieldset->addType('plan_image','MD_Membership_Block_Varien_Data_Form_Element_Plan_Image');
        $wysiwygConfig = Mage::getSingleton('md_membership/wysiwyg_config')->getConfig(
            array('tab_id' => $this->getTabId())
        );
        $widgetUrl = $wysiwygConfig->getData('widget_window_url');
        $wysiwygConfig->setData('widget_window_url',  str_replace('md_membership', 'admin', $widgetUrl));
        
        $fieldset->addField('title', 'text', array(
            'label' => $helper->__('Title'),
            'class' => 'validate-alphanum-with-spaces',
            'required' => true,
            'name' => 'membership[title]',
        ));
        $fieldset->addField('description', 'editor', array(
            'label' => $helper->__('Description'),
            'class' => '',
            'style'     => 'width:46em;',
            'required' => false,
            'name' => 'membership[description]',
            'config'    => $wysiwygConfig
        ));
        $fieldset->addField('image', 'plan_image', array(
            'label' => $helper->__('Subscription Plan Image'),
            'required' => false,
            'name' => 'image'
        ));
        
        $fieldset->addField('status', 'select', array(
            'label'     => $helper->__('Status'),
            'title'     => $helper->__('Status'),
            'name'      => 'membership[status]',
            'required'  => true,
            'options'   => array(
                '1' => $helper->__('Enabled'),
                '0' => $helper->__('Disabled'),
            ),
        ));
        $field =$fieldset->addField('store_ids', 'multiselect', array(
                'name'      => 'membership[store_ids][]',
                'label'     => $helper->__('Store View'),
                'title'     => $helper->__('Store View'),
                'required'  => true,
                'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
            ));
            $renderer = $this->getLayout()->createBlock('adminhtml/store_switcher_form_renderer_fieldset_element');
            $field->setRenderer($renderer);
        
            $groups = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt'=> 0))
            ->load()
            ->toOptionHash();
            $groups[0] = $helper->__('---Select Group---');
            $fieldset->addField('assigned_group_id', 'select', array(
            'label'     => $helper->__('Move Customer To Group'),
            'title'     => $helper->__('Move Customer To Group'),
            'name'      => 'membership[assigned_group_id]',
            'required'  => false,
            'options'   => $groups,
        ));
            
        $fieldset->addField('url_key', 'text', array(
            'label' => $helper->__('Url Key'),
            'class' => 'validate-identifier',
            'required' => false,
            'name' => 'membership[url_key]',
        ));
            
        $fieldset2 = $form->addFieldset('membership_plan_payments', array('legend' => $helper->__('Membership Payment Information')));
        
        
        $fieldset2->addField('initial_amount', 'text', array(
            'label' => $helper->__('Initial Fee'),
            'class' => 'validate-zero-or-greater',
            'required' => false,
            'name' => 'membership[initial_amount]',
        ));
        $fieldset2->addField('amount', 'text', array(
            'label' => $helper->__('Subscription Amount'),
            'class' => 'required-entry validate-greater-than-zero',
            'required' => true,
            'name' => 'membership[amount]',
        ));
        $fieldset2->addField('billing_period', 'select', array(
            'label'     => $helper->__('Billing Period'),
            'title'     => $helper->__('Billing Period'),
            'name'      => 'membership[billing_period]',
            'required'  => true,
            'options'   => array(
                MD_Membership_Model_Plans::BILLING_PERIOD_DAILY=>$helper->__('Day (Supportted for Paypal only)'),
                MD_Membership_Model_Plans::BILLING_PERIOD_WEEKLY=>$helper->__('Week'),
                MD_Membership_Model_Plans::BILLING_PERIOD_MONTHLY=>$helper->__('Month'),
                MD_Membership_Model_Plans::BILLING_PERIOD_BIMONTHLY=>$helper->__('Bi Month'),
                MD_Membership_Model_Plans::BILLING_PERIOD_QUARTERLY=>$helper->__('Quarter'),
                MD_Membership_Model_Plans::BILLING_PERIOD_YEARLY=>$helper->__('Year')
            ),
        ));
        $fieldset2->addField('is_limited', 'select', array(
            'label'     => $helper->__('Occurrence Type'),
            'title'     => $helper->__('Occurrence Type'),
            'name'      => 'membership[is_limited]',
            'required'  => true,
            'options'   => array(
                MD_Membership_Model_Plans::SUBSCRIPTION_LIFETIME=>$helper->__('Lifetime'),
                MD_Membership_Model_Plans::SUBSCRIPTION_LIMITED=>$helper->__('Limited')
            ),
        ));
        
        $fieldset2->addField('total_occurences', 'text', array(
            'label' => $helper->__('Total Occurrences'),
            'class' => $totalOccurencesClass,
            //'required' => true,
            'name' => 'membership[total_occurences]',
            'disabled'=>$dayOfMonthDisabled,
        ));
        
        $fieldset2->addField('max_failed_payment', 'text', array(
            'label' => $helper->__('Maximum failed payment'),
            'class' => 'validate-zero-or-greater',
            'required' => false,
            'name' => 'membership[max_failed_payment]',
        ));
        $fieldset2->addField('start_date_defined', 'select', array(
            'label'     => $helper->__('Start Date Defined'),
            'title'     => $helper->__('Start Date Defined'),
            'name'      => 'membership[start_date_defined]',
            'required'  => true,
            'options'   => array(
                MD_Membership_Model_Plans::DEFINED_BY_CUSTOMER=>$helper->__('Defined by customer'),
                MD_Membership_Model_Plans::DEFINED_PURCHASE=>$helper->__('Moment of purchase'),
                MD_Membership_Model_Plans::DEFINED_LAST_DAY_OF_MONTH=>$helper->__('Last day of current month'),
                MD_Membership_Model_Plans::DEFINED_DAY_OF_MONTH=>$helper->__('Exact day of month'),
            ),
        ));
        $fieldset2->addField('day_of_month', 'text', array(
            'label' => $helper->__('Day Of Month'),
            'class' => $dayOfMonthClass,
            'required' => false,
            'name' => 'membership[day_of_month]',
            'disabled'=>$dayOfMonthDisabled,
        ));
        
        $fieldset2->addField('trial_period_count', 'text', array(
            'label' => $helper->__('Number Of Occurrences for Trial Period'),
            'class' => 'validate-zero-or-greater validate-mambership-trial-periods',
            'required' => false,
            'name' => 'membership[trial_period_count]',
        ));
        
        $fieldset2->addField('trial_amount', 'text', array(
            'label' => $helper->__('Trial Period Amount'),
            'class' => 'validate-zero-or-greater',
            'required' => false,
            'name' => 'membership[trial_amount]',
        ));
        
        if (Mage::getSingleton('adminhtml/session')->getMembershipData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getMembershipData());
            Mage::getSingleton('adminhtml/session')->setMembershipData(null);
        } elseif (Mage::registry('membership_data')) {
            $form->setValues(Mage::registry('membership_data')->getData());
        }
        return parent::_prepareForm();
    }
}

