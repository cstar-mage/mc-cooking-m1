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
class MD_Membership_Block_List_Toolbar extends Mage_Core_Block_Template
{
    protected $_collection = null;
    protected $_pageVarName     = 'p';
    protected $_orderVarName        = 'order';
    protected $_directionVarName    = 'dir';
    protected $_modeVarName         = 'mode';
    protected $_limitVarName        = 'limit';
    protected $_availableOrder      = array();
    protected $_availableMode       = array();
    protected $_enableViewSwitcher  = true;
    protected $_isExpanded          = true;
    protected $_orderField          = null;
    protected $_direction           = 'asc';
    protected $_viewMode            = null;
    protected $_availableLimit  = array();
    protected $_defaultAvailableLimit  = array(10=>10,20=>20,50=>50);
    protected $_paramsMemorizeAllowed = true;
    protected $_membershipUrl = '';
    
    protected function _construct()
    {
        parent::_construct();
        $urlKey = trim(Mage::getStoreConfig("md_membership/membership_list/url_key"),'/');
        $suffix = trim(Mage::getStoreConfig("md_membership/membership_list/url_suffix"),'/');
        $urlKey .= (strlen($suffix) > 0 || $suffix != '') ? '.'.str_replace('.','',$suffix): '/';
        $this->_membershipUrl  = $urlKey;
        $this->_orderField = 'title';
        $this->_availableOrder = array('title'=>$this->__('Title'),'created_at'=>$this->__('Latest'),'amount'=>$this->__('Price'));
        $this->_availableMode = array('grid' => $this->__('Grid'), 'list' =>  $this->__('List'));
        $this->setTemplate('md/membership/list/toolbar.phtml');
    }
    
    public function setCollection($collection)
    {
        $this->_collection = $collection;
        $this->_collection->setCurPage($this->getCurrentPage());
        
        // we need to set pagination only if passed value integer and more that 0
        $limit = (int)$this->getLimit();
        if ($limit) {
            $this->_collection->setPageSize($limit);
        }
        if ($this->getCurrentOrder()) {
            $this->_collection->setOrder($this->getCurrentOrder(), $this->getCurrentDirection());
        }
        return $this;
    }
    
    public function getCollection()
    {
        return $this->_collection;
    }
    
    public function getPageVarName()
    {
        return $this->_pageVarName;
    }
    
    public function getOrderVarName()
    {
        return $this->_orderVarName;
    }
    
    public function getDirectionVarName()
    {
        return $this->_directionVarName;
    }
    
    public function getModeVarName()
    {
        return $this->_modeVarName;
    }
    
    public function getLimitVarName()
    {
        return $this->_limitVarName;
    }
    
    public function getCurrentPage()
    {
        if ($page = (int) $this->getRequest()->getParam($this->getPageVarName())) {
            return $page;
        }
        return 1;
    }
    
    public function getCurrentOrder()
    {
        $order = $this->_getData('_current_grid_order');
        if ($order) {
            return $order;
        }

        $orders = $this->getAvailableOrders();
        $defaultOrder = $this->_orderField;

        if (!isset($orders[$defaultOrder])) {
            $keys = array_keys($orders);
            $defaultOrder = $keys[0];
        }

        $order = $this->getRequest()->getParam($this->getOrderVarName());
        if ($order && isset($orders[$order])) {
            if ($order == $defaultOrder) {
                Mage::getSingleton('md_membership/session')->unsSortOrder();
            } else {
                $this->_memorizeParam('sort_order', $order);
            }
        } else {
            $order = Mage::getSingleton('md_membership/session')->getSortOrder();
        }
        // validate session value
        if (!$order || !isset($orders[$order])) {
            $order = $defaultOrder;
        }
        $this->setData('_current_grid_order', $order);
        return $order;
    }
    
    public function getCurrentDirection()
    {
        $dir = $this->_getData('_current_grid_direction');
        if ($dir) {
            return $dir;
        }

        $directions = array('asc', 'desc');
        $dir = strtolower($this->getRequest()->getParam($this->getDirectionVarName()));
        if ($dir && in_array($dir, $directions)) {
            if ($dir == $this->_direction) {
                Mage::getSingleton('md_membership/session')->unsSortDirection();
            } else {
                $this->_memorizeParam('sort_direction', $dir);
            }
        } else {
            $dir = Mage::getSingleton('md_membership/session')->getSortDirection();
        }
        // validate direction
        if (!$dir || !in_array($dir, $directions)) {
            $dir = $this->_direction;
        }
        $this->setData('_current_grid_direction', $dir);
        return $dir;
    }
    
    public function disableParamsMemorizing()
    {
        $this->_paramsMemorizeAllowed = false;
        return $this;
    }

    protected function _memorizeParam($param, $value)
    {
        $session = Mage::getSingleton('md_membership/session');
        if ($this->_paramsMemorizeAllowed && !$session->getParamsMemorizeDisabled()) {
            $session->setData($param, $value);
        }
        return $this;
    }
    
    public function setDefaultOrder($field)
    {
        if (isset($this->_availableOrder[$field])) {
            $this->_orderField = $field;
        }
        return $this;
    }
    
    public function setDefaultDirection($dir)
    {
        if (in_array(strtolower($dir), array('asc', 'desc'))) {
            $this->_direction = strtolower($dir);
        }
        return $this;
    }
    
    public function getAvailableOrders()
    {
        return $this->_availableOrder;
    }
    
    public function setAvailableOrders($orders)
    {
        $this->_availableOrder = $orders;
        return $this;
    }
    
    public function addOrderToAvailableOrders($order, $value)
    {
        $this->_availableOrder[$order] = $value;
        return $this;
    }
    
    public function removeOrderFromAvailableOrders($order)
    {
        if (isset($this->_availableOrder[$order])) {
            unset($this->_availableOrder[$order]);
        }
        return $this;
    }
    
    public function isOrderCurrent($order)
    {
        return ($order == $this->getCurrentOrder());
    }
    
    public function getOrderUrl($order, $direction)
    {
        if (is_null($order)) {
            $order = $this->getCurrentOrder() ? $this->getCurrentOrder() : $this->_availableOrder[0];
        }
        return $this->getPagerUrl(array(
            $this->getOrderVarName()=>$order,
            $this->getDirectionVarName()=>$direction,
            $this->getPageVarName() => null
        ));
    }
    
    public function getPagerUrl($params=array())
    {
        $urlParams = array();
        $urlParams['_current']  = true;
        $urlParams['_escape']   = true;
        $urlParams['_use_rewrite']   = true;
        $urlParams['_query']    = $params;
        return str_replace('/?','?',$this->getUrl($this->_membershipUrl, $urlParams));
    }
    
    public function getCurrentMode()
    {
        $mode = $this->_getData('_current_grid_mode');
        if ($mode) {
            return $mode;
        }
        $modes = array_keys($this->_availableMode);
        $defaultMode = current($modes);
        $mode = $this->getRequest()->getParam($this->getModeVarName());
        if ($mode) {
            if ($mode == $defaultMode) {
                Mage::getSingleton('md_membership/session')->unsDisplayMode();
            } else {
                $this->_memorizeParam('display_mode', $mode);
            }
        } else {
            $mode = Mage::getSingleton('md_membership/session')->getDisplayMode();
        }

        if (!$mode || !isset($this->_availableMode[$mode])) {
            $mode = $defaultMode;
        }
        $this->setData('_current_grid_mode', $mode);
        return $mode;
    }
    
    public function isModeActive($mode)
    {
        return $this->getCurrentMode() == $mode;
    }
    
    public function getModes()
    {
        return $this->_availableMode;
    }
    
    public function setModes($modes)
    {
        if(!isset($this->_availableMode)){
            $this->_availableMode = $modes;
        }
        return $this;
    }
    
    public function getModeUrl($mode)
    {
        return $this->getPagerUrl( array($this->getModeVarName()=>$mode, $this->getPageVarName() => null) );
    }
    
    public function disableViewSwitcher()
    {
        $this->_enableViewSwitcher = false;
        return $this;
    }
    
    public function enableViewSwitcher()
    {
        $this->_enableViewSwitcher = true;
        return $this;
    }
    
    public function isEnabledViewSwitcher()
    {
        return $this->_enableViewSwitcher;
    }
    
    public function disableExpanded()
    {
        $this->_isExpanded = false;
        return $this;
    }
    
    public function enableExpanded()
    {
        $this->_isExpanded = true;
        return $this;
    }
    
    public function isExpanded()
    {
        return $this->_isExpanded;
    }
    public function getDefaultPerPageValue()
    {
        return 5;
    }
    
    public function addPagerLimit($mode, $value, $label='')
    {
        if (!isset($this->_availableLimit[$mode])) {
            $this->_availableLimit[$mode] = array();
        }
        $this->_availableLimit[$mode][$value] = empty($label) ? $value : $label;
        return $this;
    }
    
    public function getAvailableLimit()
    {
        $currentMode = $this->getCurrentMode();
        if (in_array($currentMode, array('list', 'grid'))) {
            return $this->_getAvailableLimit($currentMode);
        } else {
            return $this->_defaultAvailableLimit;
        }
    }
    
    protected function _getAvailableLimit($mode)
    {
        return (array(5=>5,10=>10,15=>15) + array('all'=>$this->__('All')));
    }
    
    public function getLimit()
    {
        $limit = $this->_getData('_current_limit');
        if ($limit) {
            return $limit;
        }

        $limits = $this->getAvailableLimit();
        $defaultLimit = $this->getDefaultPerPageValue();
        if (!$defaultLimit || !isset($limits[$defaultLimit])) {
            $keys = array_keys($limits);
            $defaultLimit = $keys[0];
        }

        $limit = $this->getRequest()->getParam($this->getLimitVarName());
        if ($limit && isset($limits[$limit])) {
            if ($limit == $defaultLimit) {
                Mage::getSingleton('md_membership/session')->unsLimitPage();
            } else {
                $this->_memorizeParam('limit_page', $limit);
            }
        } else {
            $limit = Mage::getSingleton('md_membership/session')->getLimitPage();
        }
        if (!$limit || !isset($limits[$limit])) {
            $limit = $defaultLimit;
        }

        $this->setData('_current_limit', $limit);
        return $limit;
    }
    
    public function getLimitUrl($limit)
    {
        return $this->getPagerUrl(array(
            $this->getLimitVarName() => $limit,
            $this->getPageVarName() => null
        ));
    }

    public function isLimitCurrent($limit)
    {
        return $limit == $this->getLimit();
    }
    
    public function getFirstNum()
    {
        $collection = $this->getCollection();
        return $collection->getPageSize()*($collection->getCurPage()-1)+1;
    }

    public function getLastNum()
    {
        $collection = $this->getCollection();
        return $collection->getPageSize()*($collection->getCurPage()-1)+$collection->count();
    }

    public function getTotalNum()
    {
        return $this->getCollection()->getSize();
    }

    public function isFirstPage()
    {
        return $this->getCollection()->getCurPage() == 1;
    }

    public function getLastPageNum()
    {
        return $this->getCollection()->getLastPageNumber();
    }
    public function getPagerHtml()
    {
        $pagerBlock = $this->getChild('membership_list_toolbar_pager');
        if ($pagerBlock instanceof Varien_Object) {
            /* @var $pagerBlock Mage_Page_Block_Html_Pager */
            $pagerBlock->setAvailableLimit($this->getAvailableLimit());

            $pagerBlock->setUseContainer(false)
                ->setShowPerPage(false)
                ->setShowAmounts(false)
                ->setLimitVarName($this->getLimitVarName())
                ->setPageVarName($this->getPageVarName())
                ->setLimit($this->getLimit())
                ->setFrameLength(5)
                ->setJump(null)
                ->setCollection($this->getCollection());

            return $pagerBlock->toHtml();
        }

        return '';
    }
}

