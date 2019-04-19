<?php
/**
 * Orders Export and Import
 *
 * @category:    Aitoc
 * @package:     Aitoc_Aitexporter
 * @version      1.2.11
 * @license:     xwB5BO6b8ADSZdWMZBvj82HAnNdHAZvfJuyG2JiSFP
 * @copyright:   Copyright (c) 2015 AITOC, Inc. (http://www.aitoc.com)
 */
class Aitoc_Aitexporter_Block_Export_Edit_Tab_History extends Mage_Adminhtml_Block_Widget_Grid
{
    private $_helper;
    
    public function __construct()
    {
        parent::__construct();
        $this->_helper = Mage::helper('aitexporter');
        $this->setId('historyGrid');
        $this->setDefaultSort('dt');
        $this->setDefaultDir('DESC');
        $this->setTitle($this->_helper->__('Exported Orders'));
        $this->setUseAjax(true);
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('aitexporter/export_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    public function getGridUrl()
    {
        return $this->getUrl('*/*/historyGrid');
    }

    protected function _prepareColumns()
    {
        $yesNoOptions = array(0 => $this->_helper->__('No'), 
                                1 => $this->_helper->__('Yes'));

        $this->addColumn('filename', array(
            'header'    => $this->_helper->__('File Name'),
            'index'     =>'filename',
        ));
        
        $this->addColumn('is_ftp_upload', array(
            'header'    => $this->_helper->__('FTP-Uploaded'),
            'index'     =>'is_ftp_upload',
            'type'      =>'options',
            'options'   => $yesNoOptions,
        ));
        
        $this->addColumn('is_email', array(
            'header'    => $this->_helper->__('Emailed'),
            'index'     =>'is_email',
            'type'      =>'options',
            'options'   => $yesNoOptions,
        ));

        $this->addColumn('is_cron', array(
            'header'    => $this->_helper->__('Cron'),
            'index'     =>'is_cron',
            'type'      =>'options',
            'options'   => $yesNoOptions,
        ));

        $storesCollection = Mage::app()->getStores();
        $stores = array(0 => Mage::helper('aitexporter')->__('All'));
        foreach ($storesCollection as $store)
        {
            $stores[$store->getId()] = $store->getCode();
        }

        $this->addColumn('store_id', array(
            'header'    => $this->_helper->__('Store'),
            'index'     =>'store_id',
            'type'      =>'options',
            'options'   => $stores,
            ));

        $this->addColumn('dt', array(
            'header'    => $this->_helper->__('Exported At'),
            'index'     => 'dt',
            'type'      => 'datetime',
            ));

        $this->addColumn('action', array(
        	'header'    => $this->_helper->__('Action'),
            'type'      => 'action', 
            'getter'    => 'getId', 
            'actions'   => array(
                array(
                    'caption' => $this->_helper->__('Download'), 
                    'url'     => array('base' => '*/*/download'), 
                    'field'   => 'id', 
                    ), 
                array(
                    'caption' => $this->_helper->__('View Orders'), 
                    'url'     => array('base' => '*/*/viewLog'), 
                    'field'   => 'id', 
                    ), 
                array(
                    'caption' => $this->_helper->__('Delete'), 
                    'url'     => array('base' => '*/*/delete'), 
                    'field'   => 'id', 
                	'confirm' => Mage::helper('aitexporter')->__('Are you sure?'), 
                    ), 
                ),
            'filter'    => false,
            'sortable'  => false,
            'index'     => 'stores',
            'is_system' => true, 
            ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($item)
    {
        return $this->getUrl('*/*/viewLog', array('id' => $item->getId()));
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('export_id');
        
        $this->getMassactionBlock()->setFormFieldName('export_ids');
        $this->getMassactionBlock()->setUseSelectAll(true);

        $this->getMassactionBlock()->addItem('delete', array(
            'label'   => Mage::helper('aitexporter')->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete'),
			'confirm' => Mage::helper('aitexporter')->__('Are you sure?'), 
            ));

        return $this;
    }
    
    protected function _prepareMassactionBlock()
    {
        if(version_compare(Mage::getVersion(),'1.4.0.0','<')) 
        {
            $this->setChild('massaction', $this->getLayout()->createBlock('aitexporter/widget_grid_massaction'));
            $this->_prepareMassaction();
            if($this->getMassactionBlock()->isAvailable()) 
            {
                $this->_prepareMassactionColumn();
            }
        
            return $this;
        }
        else return parent::_prepareMassactionBlock();
    }
}