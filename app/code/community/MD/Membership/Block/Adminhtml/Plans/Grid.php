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
class MD_Membership_Block_Adminhtml_Plans_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    protected $_stores = array();
    protected $_helper = null;
    protected $_mediaPath = null;
    public function __construct()
    {
        parent::__construct();
        $this->_helper = Mage::helper('md_membership');
        $this->_stores = Mage::getModel('core/store')->getCollection()->toOptionHash();
        $this->_mediaPath = str_replace('index.php/','',Mage::getUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA));
        $this->setId('membershipPlansGrid');
        $this->setUseAjax(false);
        $this->setDefaultSort('plan_id');
	$this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }
    
    protected function _prepareCollection() {
        $collection = Mage::getModel('md_membership/plans')->getCollection()
                                ->addFieldToFilter('status',array('nin'=>array(MD_Membership_Model_Plans::SUBSCRIPTION_STATUS_ARCHIVED)));
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    public function _prepareColumns() {
        $this->addColumn('plan_id', array(
            'header' => $this->_helper->__('Id'),
            'index' => 'plan_id',
            'type'  => 'number',
            'width'=> '50px'
        ));
        $this->addColumn('store_ids',array(
           'header'=> $this->_helper->__('Store'),
                'index'     => 'store_ids',
                'type'      => 'store',
                'store_all'     => true,
                'store_view'    => true,
                'frame_callback' => array($this, 'decorateStores'),
                'sortable'      => false,
            'width'=> '60px',
        ));
        $this->addColumn('title', array(
            'header' => $this->_helper->__('Name'),
            'index' => 'title',
        ));
        $this->addColumn('image', array(
            'header' => $this->_helper->__('Image'),
            'index' => 'image',
            'width'=> '50px',
            'filter'=>false,
            'frame_callback' => array($this, 'decorateImage'),
        ));
        $this->addColumn('billing_period', array(
            'header' => $this->_helper->__('Period'),
            'index' => 'billing_period',
            'width'=> '10px',
            'type'=>'options',
            'options'=>$this->_helper->getSubscriptionPlanLabel(null),
        ));
        $this->addColumn('amount',array(
           'header'=>$this->_helper->__('Amount'),
            'index'=>'amount',
	    'type'=>'text',
            'filter'=>false,
            'frame_callback' => array($this, 'decorateAmount'),
            'width'=> '50px',
        ));
        
        $this->addColumn('status',array(
           'header'=>$this->_helper->__('Status'),
            'index'=>'status',
	    'type'=>'options',
	    'options'=>array(
                            MD_Membership_Model_Plans::SUBSCRIPTION_STATUS_DISABLED=>'Disabled',
                            MD_Membership_Model_Plans::SUBSCRIPTION_STATUS_ENABLED=>'Enabled',
                        ),
            'frame_callback' => array($this, 'decorateStatus'),
            'width'=> '50px',
        ));
        $this->addColumn('created_at',array(
           'header'=>$this->_helper->__('Created At'),
            'index'=>'created_at',
			'type'=>'datetime',
			'gmtoffset' => true,
            'width'=> '50px',
        ));
        $this->addColumn('updated_at',array(
            'header'=>$this->_helper->__('Updated At'),
            'index'=>'updated_at',
			'type'=>'datetime',
			'gmtoffset' => true,
            'width'=> '50px',
        ));
        return parent::_prepareColumns();
    }
    
    public function decorateStores($value, $row, $column, $isExport)
    {
        $stores = explode(",",$row->getStoreIds());
        
        $this->_stores[0] = $this->_helper->__('All Store Views');
        $string = array();
        
        foreach($stores as $store){
            $string[] = $this->_stores[$store];
        }
        
        return implode('<br />',$string);
    }
    
    public function decorateStatus($value, $row, $column, $isExport)
    {
        $class = '';
            if ($row->getStatus()) {
                $cell = '<span class="grid-severity-notice"><span>'.$value.'</span></span>';
            } else {
                $cell = '<span class="grid-severity-critical"><span>'.$value.'</span></span>';
            }
        return $cell;
    }
    
    public function decorateAmount($value, $row, $column, $isExport)
    {
        $string = '';
        $store = $this->_getStore();
        if($value){
            $string .= '<strong>'.Mage::helper('core')->currencyByStore($value,$store,true,false).'</strong>';
        }
        return $string;
    }
    
    public function decorateImage($value, $row, $column, $isExport)
    {
        $string = '';
        //if($value){
            $string .= '<img src="'.str_replace('index.php/','',$row->getImageUrl(100,100)).'" height="50px" width="50px" />';
        //}
        return $string;
    }
    
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('plan_id');
		$this->getMassactionBlock()->setFormFieldName('membership');
		$this->getMassactionBlock()->addItem('delete',array(
			'label'=>$this->_helper->__('Delete'),
			'url'=>$this->getUrl('*/*/massDelete'),
			'confirm'=>'Are you sure?'
		));
		$this->getMassactionBlock()->addItem('status',array(
			'label'=>$this->_helper->__('Change Status'),
			'url'=>$this->getUrl('*/*/massStatus'),
			'additional'=>array(
				'visibility'=>array(
					'name'=>'status',
					'type'=>'select',
					'class'=>'required-entry',
					'label'=>$this->_helper->__('Status'),
					'values'=>array(
						1=>$this->_helper->__('Enabled'),
						0=>$this->_helper->__('Disabled'),
					)
				)
			)
		));
		 return parent::_prepareMassaction();
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/mdmembership_index/edit',array('id'=>$row->getId()));
    }
    
    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }
}

