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
class MD_Membership_Block_List extends Mage_Core_Block_Template
{
    protected $_defaultToolbarBlock = 'md_membership/list_toolbar';
    protected $_membershipPlansCollection = null;
    protected $_defaultColumnCount = 3;
    protected $_columnCountLayoutDepend = array();
    public function __construct() {
        parent::__construct();
        $this->setTemplate('md/membership/list.phtml');
    }
    
    protected function _getPlans()
    {
        if (is_null($this->_membershipPlansCollection)) {
            $this->_membershipPlansCollection = Mage::getModel('md_membership/plans')->getMembershipStorePlans();
        }
        return $this->_membershipPlansCollection;
    }
    
    public function getLoadedMembershipPlans()
    {
        return $this->_getPlans();
    }
    
    public function getMode()
    {
        return $this->getChild('toolbar')->getCurrentMode();
    }
    protected function _prepareLayout()
    {
        if($breadcrumbs = $this->getLayout()->getBlock('breadcrumbs'))
        {
            $helper = Mage::helper('md_membership');
            $breadcrumbs->addCrumb('home', array('label'=>Mage::helper('cms')->__('Home'), 'title'=>Mage::helper('cms')->__('Go to Home Page'), 'link'=>Mage::getBaseUrl()));
            $breadcrumbs->addCrumb('membership_list_page', array('label'=>$helper->getMembershipLinkTitle(), 'title'=>$helper->getMembershipLinkTitle()));
        }        
        return parent::_prepareLayout();
    }
    
    protected function _beforeToHtml() {
        $toolbar = $this->getToolbarBlock();
        
        $collection = $this->_getPlans();
        
        if ($orders = $this->getAvailableOrders()) {
            $toolbar->setAvailableOrders($orders);
        }
        if ($sort = $this->getSortBy()) {
            $toolbar->setDefaultOrder($sort);
        }
        if ($dir = $this->getDefaultDirection()) {
            $toolbar->setDefaultDirection($dir);
        }
        if ($modes = $this->getModes()) {
            $toolbar->setModes($modes);
        }
        $toolbar->setCollection($collection);
        $this->setChild('toolbar', $toolbar);
        $this->_getPlans()->load();
        return parent::_beforeToHtml();
    }
    
    public function getToolbarBlock()
    {
        if ($blockName = $this->getToolbarBlockName()) {
            if ($block = $this->getLayout()->getBlock($blockName)) {
                return $block;
            }
        }
        $block = $this->getLayout()->createBlock($this->_defaultToolbarBlock, microtime());
        return $block;
    }
    
    public function getToolbarHtml()
    {
        return $this->getChildHtml('toolbar');
    }
    
    public function getColumnCount()
    {
        if (!$this->getData('column_count')) {
            $pageLayout = $this->getPageLayout();
            if ($pageLayout && $this->getColumnCountLayoutDepend($pageLayout->getCode())) {
                $this->setData(
                    'column_count',
                    $this->getColumnCountLayoutDepend($pageLayout->getCode())
                );
            } else {
                $this->setData('column_count', $this->_defaultColumnCount);
            }
        }

        return (int) $this->getData('column_count');
    }

    /**
     * Add row size depends on page layout
     *
     * @param string $pageLayout
     * @param int $columnCount
     * @return Mage_Catalog_Block_Product_List
     */
    public function addColumnCountLayoutDepend($pageLayout, $columnCount)
    {
        $this->_columnCountLayoutDepend[$pageLayout] = $columnCount;
        return $this;
    }
    
    public function getPageLayout()
    {
        echo "<pre>";print_r(Mage::helper('page/layout')->getCurrentPageLayout());exit;
        return Mage::helper('page/layout')->getCurrentPageLayout();
    }
    
    public function getColumnCountLayoutDepend($pageLayout)
    {
        if (isset($this->_columnCountLayoutDepend[$pageLayout])) {
            return $this->_columnCountLayoutDepend[$pageLayout];
        }

        return false;
    }
}

