<?php
class BroSolutions_All_Block_Adminhtml_Catalog_Product_Fastclone extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'group_id';
        $this->_blockGroup = 'brosolutionsall';
        $this->_controller = 'adminhtml_catalog_product_fastclone';
        if (!$this->hasData('template')) {
            $this->setTemplate('widget/form/container.phtml');
        }
        $this->_addButton('return', array(
            'label'     => Mage::helper('adminhtml')->__('Return to products grid'),
            'onclick'   => 'setLocation(\''
                . $this->getUrl('*/*/index/').'\')',
            'class'     => 'back',
        ), 1);
        $this->_addButton('save', array(
            'label'     => Mage::helper('adminhtml')->__('Save'),
            'onclick'   => 'edit_form.submit();',
            'class'     => 'save',
        ), 1);

    }

    public function getSaveUrl()
    {
        return $this->getUrl('*/*/saveclone');
    }
}