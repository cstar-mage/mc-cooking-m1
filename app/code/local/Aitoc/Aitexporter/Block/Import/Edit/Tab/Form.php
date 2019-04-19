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
class Aitoc_Aitexporter_Block_Import_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $config = Mage::getSingleton('aitexporter/config')->getImportConfig();

        $fieldset = $form->addFieldset('configs', array('legend' => Mage::helper('aitexporter')->__('Configuration')));

        $fieldset->addField('try_storeviews', 'select', array(
            'label'  => Mage::helper('aitexporter')->__('Try to import orders to corresponding store views'),
            'name'   => 'try_storeviews',
            'value'  => isset($config['try_storeviews']) ? $config['try_storeviews'] : false, 
        	'values' => array(
                array('value' => 0, 'label' => Mage::helper('aitexporter')->__('No')), 
                array('value' => 1, 'label' => Mage::helper('aitexporter')->__('Yes')), 
                ), 
            'note' => Mage::helper('aitexporter')->__('If there\'s no corresponding storeview the order would be imported into storeview defined by \'Store Views\' setting'), 
            ));        
        
        $fieldset->addField('store', 'select', array(
            'label'  => Mage::helper('aitexporter')->__('Store Views'),
            'name'   => 'store', 
            'value'  => isset($config['store']) ? $config['store'] : false, 
            'values' => Mage::getModel('adminhtml/system_config_source_store')->toOptionArray(), 
            ));

        $fieldset->addField('create_customers', 'select', array(
            'label'  => Mage::helper('aitexporter')->__('Create customers based on Billing/Shipping Address'),
            'name'   => 'create_customers',
            'value'  => isset($config['create_customers']) ? $config['create_customers'] : false, 
        	'values' => array(
                array('value' => '', 'label' => Mage::helper('aitexporter')->__('No')), 
                array('value' => 'billing', 'label' => Mage::helper('aitexporter')->__('Based On Billing Address')), 
                ), 
            ));

        $fieldset->addField('behavior', 'select', array(
            'label'  => Mage::helper('aitexporter')->__('Import Behavior'),
            'name'   => 'behavior',
            'value'  => isset($config['behavior']) ? $config['behavior'] : 'append', 
        	'values' => array(
                array('value' => 'append', 'label' => Mage::helper('aitexporter')->__('Append Complex Data')), 
                array('value' => 'replace', 'label' => Mage::helper('aitexporter')->__('Replace Existing Complex Data')), 
                array('value' => 'remove', 'label' => Mage::helper('aitexporter')->__('Delete Entities')), 
                ), 
            ));

        $fieldset->addField('import_file', 'file', array(
            'label'    => Mage::helper('aitexporter')->__('Upload a file'), 
        	'name'     => 'import_file', 
        	'required' => true, 
        	'class'    => 'required-entry',
            ));

        $dataFormat = $form->addFieldset('parse', array('legend' => Mage::helper('aitexporter')->__('Data Format')));

        $dataFormat->addField('parse_type', 'select', array(
            'label'    => Mage::helper('aitexporter')->__('Type'),
            'name'     => 'parse[type]', 
            'value'  => isset($config['parse']['type']) ? $config['parse']['type'] : 'xml', 
        	'values'   => array(
                array('value' => 'xml', 'label' => Mage::helper('aitexporter')->__('XML')), 
                array('value' => 'csv', 'label' => Mage::helper('aitexporter')->__('CSV/Tab Separated')), 
                ), 
            ));

        $dataFormat->addField('parse_delimiter', 'text', array(
            'label'  => Mage::helper('aitexporter')->__('Value Delimiter'), 
        	'name'   => 'parse[delimiter]', 
            'note'   => '(' . Mage::helper('aitexporter')->__('\\t for tab') . ')', 
            'value'  => isset($config['parse']['delimiter']) ? $config['parse']['delimiter'] : ',', 
            ));

        $dataFormat->addField('parse_enclose', 'text', array(
            'label'  => Mage::helper('aitexporter')->__('Enclose Value In'), 
        	'name'   => 'parse[enclose]', 
        	'note'   => Mage::helper('aitexporter')->__('Warning! Empty value can cause problems with CSV format'),
            'value'  => isset($config['parse']['enclose']) ? $config['parse']['enclose'] : '"', 
            ));

        return parent::_prepareForm();
    }
}