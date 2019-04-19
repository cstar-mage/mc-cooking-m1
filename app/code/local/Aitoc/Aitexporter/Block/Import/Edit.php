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
class Aitoc_Aitexporter_Block_Import_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'aitexporter';
        $this->_controller = 'import';

        $this->_updateButton('save', null, array(
            'label' => Mage::helper('aitexporter')->__('Check Data'),
            'onclick'   => 'editForm.submit()',
            'class'     => 'save',
            'disabled'  => Mage::getSingleton('aitexporter/processor_config')->haveActiveProcess(),
            'id'        => 'ait_save_and_continue',
            'sort_order'=>  1
        ));
        
		$this->_addButton('cancel', array(
            'label'     => Mage::helper('adminhtml')->__('Cancel'),
            'onclick'   => 'cancelImport()',
            'url' =>$this->getUrl('*/*/cancel', array('redirect' => 'import', 'store' => Mage::app()->getStore()->getId())),
			'class'     => 'cancel',
			'style' => 'position: relative; z-index: 9999;',
            'disabled'  => !Mage::getSingleton('aitexporter/processor_config')->haveActiveProcess(),
            'id'        => 'cancel'
        ), -100);        
        
        
        $this->_formScripts[] = "
			function cancelImport(){
                document.location ='" . $this->getUrl('*/*/cancel', array('redirect' => 'import', 'store' => Mage::app()->getStore()->getId())) . "';
            }
        
            ";        
        
        $this->_removeButton('delete');
        $this->_removeButton('reset');
        $this->_removeButton('back');
    }

    public function getHeaderText()
    {
        return Mage::helper('aitexporter')->__('Aitoc Order Import');
    }
}