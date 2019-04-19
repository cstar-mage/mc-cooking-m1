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
class Aitoc_Aitexporter_Block_Import_Edit_Tab_History extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('history_grid');
        $this->setDefaultSort('dt', 'desc');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('aitexporter/import')->getCollection();
        /* @var $collection Aitoc_Aitexporter_Model_Mysql4_Import_Collection */

        $collection->joinErrorsTable();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('filename', array(
            'header'    => Mage::helper('aitexporter')->__('File Name'),
            'index'     => 'filename', 
            ));

        $this->addColumn('dt', array(
            'header'    => Mage::helper('aitexporter')->__('Created at'),
            'type'      => 'datetime',
            'index'     => 'dt',
            'width'     => '150px', 
            ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('aitexporter')->__('Status'),
            'index'     => 'status', 
            'width'     => '100px',
        	'type'      => 'options', 
            'options'   => Mage::getSingleton('aitexporter/import')->getStatuses(), 
            ));

        $this->addColumn('errors_count', array(
            'header'    => Mage::helper('aitexporter')->__('Number of Errors'),
            'index'     => 'errors_count',
            'type'      => 'number', 
            ));

        $this->addColumn('action', array(
            'header'    => Mage::helper('aitexporter')->__('Log Files'),
            'width'     => '100px',
            'type'      => 'action',
            'getter'    => 'getId',
            'actions'   => array(array(
                'caption' => Mage::helper('sales')->__('View Log'), 
                'url'     => array('base' => '*/*/viewLog'), 
                'field'   => 'id', 
                )),
            'filter'    => false,
            'sortable'  => false,
            'index'     => 'stores',
            'is_system' => true, 
            ));

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/history', array('_current' => true));
    }

    public function getRowUrl($item)
    {
        return $this->getUrl('*/*/viewLog', array('id' => $item->getId()));
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('import_id');
        $this->getMassactionBlock()->setFormFieldName('import_ids');
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