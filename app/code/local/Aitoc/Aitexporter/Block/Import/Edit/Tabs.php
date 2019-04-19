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
class Aitoc_Aitexporter_Block_Import_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('import_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('aitexporter')->__('Import Orders'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label'   => Mage::helper('aitexporter')->__('Configuration'),
            'title'   => Mage::helper('aitexporter')->__('Configuration'),
            'content' => $this->getLayout()->createBlock('aitexporter/import_edit_tab_form')->toHtml(), 
            ));

        $this->addTab('history_section', array(
            'label'   => Mage::helper('aitexporter')->__('History'),
            'title'   => Mage::helper('aitexporter')->__('History'),
            'content' => $this->getLayout()->createBlock('aitexporter/import_edit_tab_history')->toHtml(), 
            ));
        
        $this->addTab('processor', array(
                'label'   => Mage::helper('aitexporter')->__('Processor'),
                'title'   => Mage::helper('aitexporter')->__('Processor'),
                'content' => $this->getLayout()->createBlock('aitexporter/processor')->toHtml(),
                'class'   => 'aitexporter_processor_tab',
        ));
        
        if(Mage::getSingleton('aitexporter/processor_config')->haveActiveProcess())
        {
            $this->setActiveTab('processor');
        }     

        return parent::_beforeToHtml();
    }    
}