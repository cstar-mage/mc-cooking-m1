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
class MD_Membership_Block_Adminhtml_Plans_Edit_Tab_Subscriber extends Mage_Adminhtml_Block_Widget_Grid
{
    protected $_helper = null;
    protected $_mediaPath = null;
    public function __construct()
    {
        parent::__construct();
        $this->_helper = Mage::helper('md_membership');
        $this->_stores = Mage::getModel('core/store')->getCollection()->toOptionHash();
        $this->_mediaPath = Mage::getUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $this->setId('membershipSubscribersGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('subscriber_id');
	$this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }
    
    protected function _prepareCollection() {
        $collection = Mage::getModel('md_membership/subscribers')->getCollection()
                        ->addFieldToFilter('plan_id',$this->getRequest()->getParam('id'));

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    public function _prepareColumns() {
        $this->addColumn('subscriber_id', array(
            'header' => $this->_helper->__('Id'),
            'index' => 'subscriber_id',
            'width'     => '15',
            'type'  => 'number',
        ));
        $this->addColumn('profile_id', array(
            'header' => $this->_helper->__('Profile Id'),
            'index' => 'profile_id',
            'type'  => 'text',
        ));
        $this->addColumn('name', array(
            'header' => $this->_helper->__('Name'),
            'index' => 'name',
        ));
        
        $this->addColumn('email', array(
            'header'    => $this->_helper->__('Email'),
            'width'     => '150',
            'index'     => 'email'
        ));
        
        $groups = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt'=> 0))
            ->load()
            ->toOptionHash();

        $this->addColumn('group', array(
            'header'    =>  $this->_helper->__('Group'),
            'width'     =>  '100',
            'index'     =>  'group_id',
            'type'      =>  'options',
            'options'   =>  $groups,
        ));
        
        $this->addColumn('country_id', array(
            'header'    => $this->_helper->__('Country'),
            'width'     => '100',
            'type'      => 'country',
            'index'     => 'country_id',
        ));

        $this->addColumn('region', array(
            'header'    => $this->_helper->__('State/Province'),
            'width'     => '100',
            'index'     => 'region',
        ));
        
        $this->addColumn('payment_method', array(
            'header'    => $this->_helper->__('Payment Method'),
            'width'     => '100',
            'index'     => 'payment_method',
            'type'      =>  'options',
            'options'   =>  $this->_helper->getPaymentArray(),
        ));
        
        $this->addColumn('status',array(
           'header'=>$this->_helper->__('Status'),
            'index'=>'status',
	    'type'=>'options',
	    'options'=> $this->_helper->getStatusLabels(),
            'frame_callback' => array($this, 'decorateStatus')
        ));
        
        $this->addColumn('profile_start_date',array(
           'header'=>$this->_helper->__('Subscription Date'),
            'index'=>'profile_start_date',
			'type'=>'date',
			'gmtoffset' => true,
                        'width'=> '50px'
        ));
        
        return parent::_prepareColumns();
    }
    
    public function decorateStatus($value, $row, $column, $isExport)
    {
        $class = '';
        $class = '';
        switch($row->getStatus()){
            case MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_ACTIVE:
                    $class .= 'grid-severity-notice';
                    break;
            case MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_EXPIRED:
                    $class .= 'grid-severity-critical';
                    break;
            case MD_Membership_Model_Subscribers::SUBSCRIPTION_STATUS_TERMINATED:
                    $class .= 'grid-severity-major';
                    break;
            default:
                    $class .= 'grid-severity-minor';
                    break;
        }
            $cell = '<span class="'.$class.'"><span>'.$value.'</span></span>';
        return $cell;
    }
    
    protected function _prepareMassaction()
    {
                $this->setMassactionIdField('subscriber_id');
                $planId = $this->getRequest()->getParam('id',null);
		$this->getMassactionBlock()->setFormFieldName('subscribers');
		$this->getMassactionBlock()->addItem('delete',array(
			'label'=>$this->_helper->__('Delete'),
			'url'=>$this->getUrl('*/*/massSubscribersDelete',array('plan_id'=>$planId)),
			'confirm'=>'Only profile status \'Canceled\' will be deleted.Are you want to delete?'
		));
		$this->getMassactionBlock()->addItem('status',array(
			'label'=>$this->_helper->__('Change Status'),
			'url'=>$this->getUrl('*/*/massSubscribersStatus',array('plan_id'=>$planId)),
			'additional'=>array(
				'visibility'=>array(
					'name'=>'status',
					'type'=>'select',
					'class'=>'required-entry',
					'label'=>$this->_helper->__('Status'),
					'values'=>array(
                                            4 => $this->_helper->__('Cancel Profile'),
                                            3 => $this->_helper->__('Suspend Profile'),
                                            1 => $this->_helper->__('Reactivate Profile'),
                                        ),
				)
			)
		));
		 return parent::_prepareMassaction();
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/mdmembership_subscribers/view',array('id'=>$row->getId()));
    }
}