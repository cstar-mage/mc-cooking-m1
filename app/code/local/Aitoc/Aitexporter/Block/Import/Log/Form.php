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
class Aitoc_Aitexporter_Block_Import_Log_Form extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('import_log_grid');
        //$this->setDefaultSort('type', 'desc');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('aitexporter/import_error')->getCollection();
        /* @var $collection Aitoc_Aitexporter_Model_Mysql4_Import_Error_Collection */

        $collection->addFieldToFilter('import_id', Mage::registry('current_import')->getId());

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('order_increment_id', array(
            'header'    => Mage::helper('aitexporter')->__('Order #'),
            'width'     => '100px',
        	'index'     => 'order_increment_id', 
            ));

        $this->addColumn('type', array(
            'header'    => Mage::helper('aitexporter')->__('Type'),
            'index'     => 'type', 
            'width'     => '100px',
        	'type'      => 'options', 
            'options'   => Mage::getSingleton('aitexporter/import_error')->getTypes(), 
            ));

        $this->addColumn('error', array(
            'header'    => Mage::helper('aitexporter')->__('Error Text'),
            'index'     => 'error',
            'type'      => 'text', 
            'sortable'  => false,
            'filter'    => false,
            ));

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/viewLogGrid', array('_current' => true));
    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'     => 'log_form', 
            'action' => $this->getUrl('*/*/import'), 
            'method' => 'post', 
            ));

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}