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
class Aitoc_Aitexporter_Block_Export_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'aitexporter';
        $this->_controller = 'export';

        $this->_removeButton('delete');
        $this->_removeButton('reset');
        $this->_removeButton('back');
        
        $this->_updateButton('save', 'onclick', 'checkForm();editForm.submit();');
        $this->_addButton('saveandexport', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Export'),
            'onclick'   => 'checkForm();saveAndExport();editForm.submit()',
            'class'     => 'saveAndExport',
            'disabled'  => Mage::getSingleton('aitexporter/processor_config')->haveActiveProcess(),
            'id'        => 'ait_save_and_continue'
        ), -100);
		
		$this->_addButton('cancel', array(
            'label'     => Mage::helper('adminhtml')->__('Cancel'),
            'onclick'   => 'cancelExport();editForm.submit()',
            'url' =>$this->getUrl('*/*/cancel', array('redirect' => 'export')),
			'class'     => 'cancel',
			'style' => 'position: relative; z-index: 9999;',
            'disabled'  => !Mage::getSingleton('aitexporter/processor_config')->haveActiveProcess(),
            'id'        => 'cancel'
        ), -100);

        $this->_formScripts[] = "
            function loadProfile(){
                var id = \$('profileSelect').value;
                if(id > 0) {
                    var url = '" . $this->getUrl('*/*/index') . "';
                    window.location.href = url + 'profile/'+id+'/';
                }
            }
        
            function deleteProfile(){
                var id = \$('profileSelect').value;
                if(id > 0) {
                    var url = '" . $this->getUrl('*/*/deleteProfile') . "';
                    window.location.href = url + 'profile/'+id+'/';
                }
            }
        
            function saveAndExport(){
                $('edit_form').action='" . $this->getUrl('*/*/save', array('redirect' => 'export')) . "';
            }
			
			function cancelExport(){
                $('edit_form').action='" . $this->getUrl('*/*/cancel', array('redirect' => 'export')) . "';
            }

function checkForm(event){
    if($('invoiceComment').checked || $('invoiceItem').checked)
        $('invoice').checked=true;
    if($('shipmentComment').checked || $('shipmentItem').checked || $('shipmentTracking').checked)
        $('shipment').checked=true;
    if($('creditmemoComment').checked || $('creditmemoItem').checked)
        $('creditmemo').checked=true;
    if($('orderPaymentTransaction').checked)
        $('orderPayment').checked=true;
};";
    }

    public function getHeaderText()
    {
        return Mage::helper('aitexporter')->__('Aitoc Order Export');
    }

    public function getFormActionUrl()
    {
        return $this->getUrl('*/*/save', array());
    }
}